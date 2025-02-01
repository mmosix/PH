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
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $username = Security::sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        try {
            if (Auth::login($username, $password)) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $role = $_SESSION['user']['role'];
                header("Location: /$role/dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password";
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
    <title>Login - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Construction PM Login</h2>
                
                <?php if ($registered): ?>
                    <div class="alert alert-success">Registration successful! Please login.</div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               required autofocus>
                    </div>

                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" 
                               name="password" required>
                        <i class="bi bi-eye-slash password-toggle" 
                           onclick="togglePasswordVisibility()"></i>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <a href="forgot-password.php" class="text-decoration-none">
                        Forgot Password?
                    </a>
                    <span class="mx-2">|</span>
                    <a href="register.php" class="text-decoration-none">
                        Create New Account
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Password Toggle Script -->
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }
    </script>
</body>
</html>