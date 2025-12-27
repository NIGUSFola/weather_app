<?php
// config/db.php
// Centralized PDO connection helper for the weather_app project

function db(): PDO {
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // ✅ Database credentials (XAMPP defaults)
    $host = 'localhost';
    $db   = 'weather_app';
    $user = 'root';
    $pass = '';

    try {
        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}
