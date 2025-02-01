<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!\App\Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        try {
            // Validate and sanitize input
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

            $userData = [
                'email' => $email,
                'password' => $_POST['password'],
                'confirm_password' => $_POST['confirm_password'],
                'role' => $_POST['role'] // Add role to user data
            ];

            // Register user
            if (\App\Auth::register($userData)) {
                $success = true;
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
    <title>Register</title>
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Registration successful! Please check your email to verify your account.
                <a href="login.php">Login here</a>
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

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="client">Client</option>
                        <option value="contractor">Contractor</option>
                    </select>
                </div>

                <button type="submit">Register</button>
            </form>
            
            <p>Already have an account? <a href="login.php">Login here</a></p>
        <?php endif; ?>
    </div>
</body>
</html>