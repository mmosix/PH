<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set exception handler
set_exception_handler(function($exception) {
    $logger = App\Utils\Logger::getInstance();
    
    $context = [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];
    
    if ($exception instanceof App\Exceptions\BaseException) {
        $context = array_merge($context, $exception->getContext());
    }
    
    $logger->error($exception->getMessage(), $context);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        throw $exception;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'An unexpected error occurred']);
    }
});

// Initialize database connection pool
use App\Utils\DatabaseConnectionPool;

$dbConfig = [
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USER'], // Database connection username - not related to user authentication
    'password' => $_ENV['DB_PASS']
];

DatabaseConnectionPool::initialize($dbConfig);
$db = DatabaseConnectionPool::getConnection();