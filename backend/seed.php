<?php
// backend/seed.php
// Run once to populate essential seed data for Ethiopia Weather Aggregator

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/log.php';

try {
    $pdo = db();
    echo "✅ Connected to DB\n";
    log_event("Connected to DB for seeding", "INFO", ['module' => 'seed']);

    // --- Seed Admin Account ---
    $adminEmail = 'admin@gmail.com';
    $adminPassword = 'Admin@123'; // plain text for demo
    $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, role, created_at)
        VALUES (:email, :password, 'admin', NOW())
        ON DUPLICATE KEY UPDATE role = VALUES(role)
    ");
    $stmt->execute([':email' => $adminEmail, ':password' => $adminHash]);

    echo "✅ Admin account seeded: $adminEmail / $adminPassword\n";
    log_event("Admin account seeded: $adminEmail", "INFO", ['module' => 'seed']);

    // --- Seed Cities ---
    $cities = [
        ['Addis Ababa', 'Central'],
        ['Shashamane', 'Oromia'],
        ['Hawassa', 'South'],
        ['Bahir Dar', 'Amhara'],
        ['Wolaita', 'South'],
        ['Hossana', 'Central']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO cities (name, region)
        VALUES (:name, :region)
        ON DUPLICATE KEY UPDATE region = VALUES(region)
    ");

    foreach ($cities as [$name, $region]) {
        $stmt->execute([':name' => $name, ':region' => $region]);
        echo "✅ City seeded: $name ($region)\n";
        log_event("City seeded: $name ($region)", "INFO", ['module' => 'seed']);
    }

    echo "🎉 Seeding complete!\n";
    log_event("Seeding complete", "INFO", ['module' => 'seed']);

} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    log_event("Seeding failed: " . $e->getMessage(), "ERROR", ['module' => 'seed']);
}
