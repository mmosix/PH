<?php
require_once __DIR__.'/../app/Auth.php';
require_once __DIR__.'/../app/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$success = false;
$token = $_GET['token'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $token = Security::sanitizeInput($_POST['token']);
        $password = $_POST['password'];
        
        try {
            if (Auth::resetPassword($token, $password)) {
                $success = true;
            } else {
                $error = "Invalid or expired token";
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
    <title>Reset Password - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reset-password-container {
            max-width: 500px;
            margin: 5rem auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="reset-password-container">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Reset Password</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Password reset successfully! <a href="/login.php">Login now</a>.
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="token" value="<?= $token ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" 
                                   name="password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>