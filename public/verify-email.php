<?php
require_once __DIR__.'/../app/Auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = false;
$message = "Invalid or expired verification token";
$token = $_GET['token'] ?? null;

if ($token) {
    if (Auth::verifyEmail($token)) {
        $success = true;
        $message = "Email verified successfully! You can now login.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 500px;
            margin: 5rem auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="verification-container">
        <div class="card shadow">
            <div class="card-body text-center">
                <?php if($success): ?>
                    <div class="text-success mb-3">
                        <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
                    </div>
                <?php else: ?>
                    <div class="text-danger mb-3">
                        <i class="bi bi-x-circle-fill" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>
                
                <h2 class="card-title mb-3"><?= $message ?></h2>
                
                <?php if($success): ?>
                    <a href="/login.php" class="btn btn-primary">Go to Login</a>
                <?php else: ?>
                    <a href="/resend-verification.php" class="btn btn-warning">
                        Resend Verification Email
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>