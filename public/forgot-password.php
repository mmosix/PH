<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!\App\Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $email = \App\Security::sanitizeInput($_POST['email']);
        
        try {
            if (\App\Auth::requestPasswordReset($email)) {
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
$csrfToken = \App\Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                If an account exists with that email, password reset instructions have been sent.
                Please check your email.
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <button type="submit">Reset Password</button>
            </form>
            
            <p><a href="login.php">Back to Login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>