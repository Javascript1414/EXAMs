<?php
require_once __DIR__ . '/../config.php';

/**
 * PDO Database Connection Singleton (using exams_lms)
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Forces native prepared statements for security
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (ENVIRONMENT === 'development') {
        die("Database Connection Failed: " . $e->getMessage());
    }
    die("A database error occurred. Please contact the administrator.");
}