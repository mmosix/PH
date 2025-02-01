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