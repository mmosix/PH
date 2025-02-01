<?php
session_start();

class Auth {
    public static function login($username, $password) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['email_verified']) {
                throw new Exception("Email not verified. Please check your inbox.");
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'wallet' => $user['wallet_address']
            ];
            return true;
        }
        return false;
    }

    public static function register($userData) {
        $pdo = Database::connect();
        
        // Validate required fields
        $required = ['username', 'password', 'email', 'role'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Check if username/email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$userData['username'], $userData['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, password, email, phone, role, wallet_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userData['username'],
            $hashedPassword,
            $userData['email'],
            $userData['phone'] ?? null,
            $userData['role'],
            $userData['wallet_address'] ?? null
        ]);

        // Generate verification token
        $userId = $pdo->lastInsertId();
        $token = self::generateVerificationToken($userId);

        // Send verification email
        $mailer = new Mailer();
        $mailer->sendVerificationEmail(
            $userData['email'],
            $userData['username'],
            $token
        );

        return true;
    }

    public static function generateVerificationToken($userId) {
        $pdo = Database::connect();
        
        // Delete existing tokens
        $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?")
            ->execute([$userId]);

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));

        $stmt = $pdo->prepare("
            INSERT INTO email_verification_tokens 
            (user_id, token, expiration)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $expiration]);
        
        return $token;
    }

    public static function verifyEmail($token) {
        $pdo = Database::connect();
        
        $stmt = $pdo->prepare("
            SELECT users.id 
            FROM email_verification_tokens
            JOIN users ON email_verification_tokens.user_id = users.id
            WHERE token = ? AND expiration > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Mark email as verified
            $pdo->prepare("UPDATE users SET email_verified = true WHERE id = ?")
                ->execute([$user['id']]);
                
            // Delete used token
            $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?")
                ->execute([$user['id']]);
                
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
        header('Location: /login.php');
        exit;
    }
}