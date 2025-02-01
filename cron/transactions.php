<?php
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../app/Database.php';

$pdo = Database::connect();

// Cleanup expired tokens
$pdo->query("DELETE FROM password_reset_tokens WHERE expiration < NOW()");
$pdo->query("DELETE FROM email_verification_tokens WHERE expiration < NOW()");