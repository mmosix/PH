<?php
require_once __DIR__.'/../app/Auth.php';
require_once __DIR__.'/../app/Security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Redirect to login page if not authenticated
    header('Location: login.php');
    exit;
}

// Get user type and redirect to appropriate dashboard
$userType = $_SESSION['user']['type'];

switch ($userType) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'client':
        header('Location: client/dashboard.php');
        break;
    case 'contractor':
        header('Location: contractor/dashboard.php');
        break;
    default:
        // If user type is not recognized, log them out and redirect to login
        session_destroy();
        header('Location: login.php');
        break;
}
exit;