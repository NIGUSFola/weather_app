<?php
// config/db.php
// Centralized PDO connection helper for the weather_app project

/**
 * Returns a PDO instance connected to the weather_app database.
 *
 * Uses a static variable to ensure only one connection is created per request.
 * Default setup assumes XAMPP (root user, no password).
 *
 * @return PDO
 */
function db(): PDO {
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // ✅ Database credentials
    $host = 'localhost';
    $db   = 'weather_app';
    $user = 'root';   // XAMPP default user
    $pass = '';       // XAMPP default has no password

    try {
        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch rows as associative arrays
            PDO::ATTR_PERSISTENT         => false,                  // avoid persistent connections for simplicity
        ]);

        return $pdo;
    } catch (PDOException $e) {
        // ✅ Log error for debugging (never expose sensitive info to users)
        error_log("DB connection failed: " . $e->getMessage());

        // ✅ Fail gracefully
        die("Database connection failed. Please check your configuration.");
    }
}
