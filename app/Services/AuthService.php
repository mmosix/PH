<?php

namespace App\Services;

use PDO;
use App\Utils\Logger;
use App\Utils\InputValidator;
use App\Exceptions\AuthException;
use App\Exceptions\DatabaseException;

class AuthService
{
    use InputValidator;

    private PDO $db;
    private Logger $logger;
    private array $config;
    private const PASSWORD_MIN_LENGTH = 8;

    public function __construct(PDO $db, Logger $logger, array $config = [])
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->config = array_merge([
            'session_lifetime' => 3600,
            'password_cost' => 12,
            'token_expiry' => 86400, // 24 hours
        ], $config);

        // Configure secure session
        $this->configureSession();
    }

    /**
     * Configure secure session settings
     */
    private function configureSession(): void
    {
        session_set_cookie_params([
            'lifetime' => $this->config['session_lifetime'],
            'path' => '/',
            'domain' => $_ENV['APP_DOMAIN'] ?? '',
            'secure' => $_ENV['SESSION_SECURE'] ?? true,
            'httponly' => $_ENV['SESSION_HTTPONLY'] ?? true,
            'samesite' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax'
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authenticate a user with email and password
     * @param string $email The user's email
     * @param string $password The password
     * @return array User data
     * @throws AuthException If credentials are invalid or email not verified
     */
    public function login(string $email, string $password): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $this->logger->info('Failed login attempt', ['email' => $email]);
                throw AuthException::invalidCredentials();
            }

            if (!$user['email_verified']) {
                $this->logger->info('Unverified email login attempt', ['email' => $email]);
                throw AuthException::emailNotVerified();
            }

            // Update last login timestamp
            $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            $this->logger->info('Successful login', ['user_id' => $user['id']]);
            
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
    public function register(array $userData): array
    {
        try {
            // Validate required fields
            $required = ['email', 'password', 'name'];
            if ($missing = $this->validateRequired($userData, $required)) {
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
            if (!$this->validateLength($userData['password'], self::PASSWORD_MIN_LENGTH, 72)) {
                throw new AuthException("Password must be between " . self::PASSWORD_MIN_LENGTH . " and 72 characters");
            }

            // Check if email exists
            $stmt = $this->db->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($existing = $stmt->fetch()) {
                throw new AuthException("Email already exists");
            }

            // Hash password
            $userData['password'] = password_hash(
                $userData['password'],
                PASSWORD_DEFAULT,
                ['cost' => $this->config['password_cost']]
            );

            // Generate verification token
            $userData['verification_token'] = $this->generateToken();
            $userData['email_verified'] = false;

            // Insert user
            $fields = implode(', ', array_keys($userData));
            $placeholders = implode(', ', array_fill(0, count($userData), '?'));
            $stmt = $this->db->prepare("INSERT INTO users ({$fields}) VALUES ({$placeholders})");
            $stmt->execute(array_values($userData));

            $userId = $this->db->lastInsertId();
            $this->logger->info('New user registered', ['user_id' => $userId]);

            unset($userData['password']);
            $userData['id'] = $userId;
            return $userData;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('register query', $e->getMessage());
        }
    }

    /**
     * Generate a verification token
     * @return string The generated token
     */
    private function generateToken(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            // Fallback to less secure but still usable method
            return md5(uniqid(mt_rand(), true));
        }
    }

    /**
     * Verify a user's email address
     * @param string $token The verification token
     * @return bool True if verification successful
     * @throws AuthException If token invalid or expired
     */
    public function verifyEmail(string $token): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET email_verified = true, 
                    verification_token = NULL 
                WHERE verification_token = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");

            if ($stmt->execute([$token, $this->config['token_expiry']]) && $stmt->rowCount() > 0) {
                $this->logger->info('Email verified', ['token' => $token]);
                return true;
            }

            throw AuthException::invalidToken();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('email verification', $e->getMessage());
        }
    }

    /**
     * Check if user is authenticated
     * @return bool Authentication status
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['last_activity']) && 
               (time() - $_SESSION['last_activity']) < $this->config['session_lifetime'];
    }

    /**
     * Get current authenticated user ID
     * @return int|null User ID if authenticated, null otherwise
     */
    public function getCurrentUserId(): ?int
    {
        return $this->isAuthenticated() ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Refresh session and update last activity
     */
    public function refreshSession(): void
    {
        if ($this->isAuthenticated()) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Log out the current user
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logger->info('User logged out', ['user_id' => $_SESSION['user_id']]);
        }

        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}