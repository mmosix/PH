<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'dash_phuser');
define('DB_PASS', 'Dawkrn[rfw^e');
define('DB_NAME', 'ph_portal');

function connect() {
    try {
        return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
