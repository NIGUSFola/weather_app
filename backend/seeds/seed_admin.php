<?php
// backend/seeds/seed_admin.php
// Seed an admin account into the database

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

$pdo = db();

// ✅ Replace with your desired admin credentials
$email    = 'admin@example.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role     = 'admin';

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, role, created_at)
        VALUES (:email, :password, :role, NOW())
        ON DUPLICATE KEY UPDATE role = VALUES(role)
    ");
    $stmt->execute([
        ':email'    => $email,
        ':password' => $password,
        ':role'     => $role
    ]);

    log_event("Seeded admin account: $email", "INFO", ['module' => 'seed']);
    echo "✅ Admin account seeded successfully\n";
} catch (Exception $e) {
    log_event("Failed to seed admin account: " . $e->getMessage(), "ERROR", ['module' => 'seed']);
    echo "❌ Failed to seed admin account\n";
}
