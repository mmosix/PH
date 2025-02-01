<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "Autoloader included successfully\n";

try {
    if (class_exists('App\Auth')) {
        echo "Auth class found in namespace App\n";
    } else {
        echo "Auth class NOT found in namespace App\n";
        
        // Debug information
        echo "\nDebug Info:\n";
        echo "Current directory: " . __DIR__ . "\n";
        echo "Autoloader path: " . realpath(__DIR__ . '/../vendor/autoload.php') . "\n";
        echo "Auth.php path: " . realpath(__DIR__ . '/../app/Auth.php') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}