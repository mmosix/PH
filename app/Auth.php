<?php

namespace App;

use App\Exceptions\AuthException;
use App\Exceptions\DatabaseException;
use App\Utils\Logger;

class Auth {
    private static Logger $logger;
    
    public static function initialize(): void
    {
        self::$logger = Logger::getInstance();
    }

    /**
     * Authenticate a user with email and password
     * @param string $email The user's email
     * @param string $password The password
     * @return array User data
     * @throws AuthException If credentials are invalid or email not verified
     */
    public static function login(string $email, string $password): array
    {
        $db = \App\Utils\DatabaseConnectionPool::getConnection();
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                self::$logger->info('Failed login attempt', ['email' => $email]);
                throw AuthException::invalidCredentials();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            self::$logger->info('Successful login', ['user_id' => $user['id']]);
            
            unset($user['password']); // Don't return sensitive data
            return $user;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('login query', $e->getMessage());
        }
    }

    /**
     * Register a new user
     * @param array $userData User registration data
     * @return array Created user data
     * @throws AuthException If validation fails or user already exists
     */
    public static function register(array $userData): array
    {
        global $db;
        
        try {
            $required = ['email', 'password', 'name', 'role'];
            $missing = array_filter($required, fn($field) => empty($userData[$field]));
            
            if (!empty($missing)) {
                throw new AuthException(
                    'Missing required fields: ' . implode(', ', $missing),
                    ['fields' => $missing]
                );
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new AuthException('Invalid email format');
            }
            
            // Validate password strength
            if (strlen($userData['password']) < 8) {
                throw new AuthException('Password must be at least 8 characters long');
            }
            
            // Check if email exists
            $stmt = $db->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($existing = $stmt->fetch()) {
                throw new AuthException("Email already exists");
            }
            
            // Hash password with strong algorithm
            $userData['password'] = password_hash(
                $userData['password'],
                PASSWORD_DEFAULT,
                ['cost' => 12]
            );
            
            // Insert user
            $fields = implode(', ', array_keys($userData));
            $values = implode(', ', array_fill(0, count($userData), '?'));
            $stmt = $db->prepare("INSERT INTO users ($fields) VALUES ($values)");
            $stmt->execute(array_values($userData));
            
            $userId = $db->lastInsertId();
            self::$logger->info('New user registered', ['user_id' => $userId]);
            
            // Generate verification token
            self::generateVerificationToken($userId);
            
            // Return user data without password
            unset($userData['password']);
            $userData['id'] = $userId;
            return $userData;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('register query', $e->getMessage());
        }
    }

    /**
     * Generate a verification token for a user
     * @param int $userId The user ID
     * @return string The generated token
     * @throws AuthException If user not found
     */
    public static function generateVerificationToken(int $userId): string
    {
        global $db;
        
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            
            $stmt = $db->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            if (!$stmt->execute([$userId, $token, $expiresAt])) {
                throw AuthException::userNotFound();
            }
            
            return $token;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('token generation', $e->getMessage());
        }
    }

    /**
     * Verify a user's email address
     * @param string $token The verification token
     * @return bool True if verification successful
     * @throws AuthException If token invalid or expired
     */
    public static function verifyEmail(string $token): bool
    {
        global $db;
        
        try {
            $stmt = $db->prepare("
                UPDATE users u
                JOIN email_verification_tokens evt ON u.id = evt.user_id
                SET u.email_verified = true, evt.token = NULL
                WHERE evt.token = ? AND evt.expires_at > NOW()
            ");
            if ($stmt->execute([$token]) && $stmt->rowCount() > 0) {
                self::$logger->info('Email verified', ['token' => $token]);
                return true;
            }
            throw AuthException::invalidToken();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('email verification', $e->getMessage());
        }
    }

    /**
     * Log out the current user
     */
    public static function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            self::$logger->info('User logged out', ['user_id' => $_SESSION['user_id']]);
        }
        
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}