<?php
// backend/install.php
// Run once to initialize the Ethiopia Weather Aggregator database schema + seed data

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/log.php';

header('Content-Type: application/json');

try {
    $pdo = db();

    // === Users table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user','admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // === Cities table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            region VARCHAR(100) DEFAULT 'Ethiopia',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // === Favorites table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            user_id INT NOT NULL,
            city_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, city_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
        )
    ");

    // === Weather cache table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS weather_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            city_id INT NOT NULL,
            type ENUM('forecast','alerts','current','radar') NOT NULL,
            payload JSON NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
        )
    ");

    // === API requests table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint VARCHAR(100) NOT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // === Logs table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('INFO','WARN','ERROR','DEBUG') NOT NULL,
            message TEXT NOT NULL,
            user_id INT NULL,
            role ENUM('user','admin') NULL,
            context JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    // === Distributed locks table ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS distributed_locks (
            name VARCHAR(100) PRIMARY KEY,
            acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // === Seed Ethiopian cities ===
    $pdo->exec("
        INSERT IGNORE INTO cities (name, region) VALUES
        ('Wolaita', 'South'),
        ('Hossana', 'Central'),
        ('Bahir Dar', 'Amhara'),
        ('Addis Ababa', 'Addis Ababa')
    ");

    // === Seed admin user ===
    $adminEmail = 'admin@example.com';
    $adminPass  = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (email, password_hash, role) VALUES (?, ?, 'admin')")
        ->execute([$adminEmail, $adminPass]);

    log_event("Database install completed successfully", "INFO", ['module'=>'install']);

    echo json_encode(['success'=>true,'message'=>'Database schema installed and seed data added']);
} catch (Exception $e) {
    log_event("Database install failed: ".$e->getMessage(), "ERROR", ['module'=>'install']);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Install failed: '.$e->getMessage()]);
}
