<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug autoloader
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify autoloader is working and class exists
if (!class_exists('App\\Auth')) {
    die('Autoloader is not working correctly. Class App\\Auth could not be found. Include path: ' . get_include_path());
}

// Start Auth system
\App\Auth::initialize();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$registered = isset($_GET['registered']);

// System already initialized above

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!\App\Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        try {
            $user = \App\Auth::login($username, $password);
            if ($user && isset($user['id'])) {
                header('Location: index.php');
                exit;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$csrfToken = \App\Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($registered): ?>
            <div class="success-message">
                Registration successful! Please login with your credentials.
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="links">
            <a href="forgot-password.php">Forgot Password?</a>
            <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>