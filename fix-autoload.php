<?php

echo "Starting autoloader fix script...\n";

// Check composer installation
if (!file_exists('composer.json')) {
    die("Error: composer.json not found in current directory\n");
}

// Verify app directory structure
if (!is_dir('app')) {
    die("Error: app directory not found\n");
}

// Verify Auth.php exists and is readable
if (!file_exists('app/Auth.php')) {
    die("Error: app/Auth.php not found\n");
}

// Check namespace in Auth.php
$auth_contents = file_get_contents('app/Auth.php');
if (strpos($auth_contents, 'namespace App;') === false) {
    die("Error: Auth.php does not contain correct namespace\n");
}

// Check if vendor directory exists
if (!is_dir('vendor')) {
    echo "Warning: vendor directory not found. Running composer install...\n";
    exec('composer install 2>&1', $output, $return_var);
    if ($return_var !== 0) {
        die("Error running composer install: " . implode("\n", $output) . "\n");
    }
}

// Force regenerate autoloader
echo "Regenerating autoloader...\n";
exec('composer dump-autoload -o 2>&1', $output, $return_var);
if ($return_var !== 0) {
    die("Error regenerating autoloader: " . implode("\n", $output) . "\n");
}

// Verify autoloader works
require_once __DIR__ . '/vendor/autoload.php';
if (!class_exists('App\\Auth')) {
    die("Error: App\\Auth class still not found after regenerating autoloader\n");
}

echo "Success! Autoloader has been fixed and verified.\n";
echo "Please ensure files in public/ directory use fully qualified class names (\\App\\Auth).\n";