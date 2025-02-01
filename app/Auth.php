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
     * Authenticate a user with username and password
     * @param string $username The username
     * @param string $password The password
     * @return array User data
     * @throws AuthException If credentials are invalid or email not verified
     */
    public static function login(string $username, string $password): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                self::$logger->info('Failed login attempt', ['username' => $username]);
                throw AuthException::invalidCredentials();
            }

            if (!$user['email_verified']) {
                self::$logger->info('Unverified email login attempt', ['username' => $username]);
                throw AuthException::emailNotVerified();
            }

            // Update last login timestamp
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
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
            $required = ['username', 'password', 'email', 'name'];
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
            
            // Check if username or email exists
            $stmt = $db->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            if ($existing = $stmt->fetch()) {
                $field = $existing['username'] === $userData['username'] ? 'username' : 'email';
                throw new AuthException("$field already exists");
            }
            
            // Hash password with strong algorithm
            $userData['password'] = password_hash(
                $userData['password'],
                PASSWORD_DEFAULT,
                ['cost' => 12]
            );
            
            // Generate verification token
            $userData['verification_token'] = bin2hex(random_bytes(32));
            $userData['email_verified'] = false;
            
            // Insert user
            $fields = implode(', ', array_keys($userData));
            $values = implode(', ', array_fill(0, count($userData), '?'));
            $stmt = $db->prepare("INSERT INTO users ($fields) VALUES ($values)");
            $stmt->execute(array_values($userData));
            
            $userId = $db->lastInsertId();
            self::$logger->info('New user registered', ['user_id' => $userId]);
            
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
            
            $stmt = $db->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
            if (!$stmt->execute([$token, $userId])) {
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
            $stmt = $db->prepare("UPDATE users SET email_verified = true WHERE verification_token = ?");
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