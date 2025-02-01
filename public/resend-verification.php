<?php
require_once __DIR__.'/../app/Auth.php';
require_once __DIR__.'/../app/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $email = Security::sanitizeInput($_POST['email']);
        
        try {
            if (Auth::resendVerificationEmail($email)) {
                $success = true;
            } else {
                $error = "Email not found in our system";
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
    <title>Resend Verification - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .resend-verification-container {
            max-width: 500px;
            margin: 5rem auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="resend-verification-container">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Resend Verification Email</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Verification email resent successfully! Please check your inbox.
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Resend Email</button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <a href="/login.php" class="text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>