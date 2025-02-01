<?php
require_once __DIR__.'/../app/Auth.php';
require_once __DIR__.'/../app/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    header("Location: /$role/dashboard.php");
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        try {
            // Sanitize inputs
            $userData = [
                'username' => Security::sanitizeInput($_POST['username']),
                'email' => Security::sanitizeInput($_POST['email']),
                'password' => $_POST['password'],
                'phone' => Security::sanitizeInput($_POST['phone']),
                'role' => 'client', // Default role
                'wallet_address' => Security::sanitizeInput($_POST['wallet_address'])
            ];

            // Register user
            if (Auth::register($userData)) {
                $success = true;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 500px;
            margin: 5rem auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="register-container">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Create Account</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Registration successful! Please check your email to verify your account.
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" 
                               name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="wallet_address" class="form-label">Wallet Address</label>
                        <input type="text" class="form-control" id="wallet_address" 
                               name="wallet_address">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    Already have an account? <a href="/login.php" class="text-decoration-none">
                        Login here
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>