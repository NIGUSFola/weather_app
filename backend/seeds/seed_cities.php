<?php
// backend/seeds/seed_cities.php
// Seed essential cities into the database

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

$pdo = db();

$cities = [
    ['name' => 'Addis Ababa'],
    ['name' => 'Shashamane'],
    ['name' => 'Bahir Dar'],
    ['name' => 'Dire Dawa'],
    ['name' => 'Mekelle']
];

foreach ($cities as $city) {
    try {
        $stmt = $pdo->prepare("INSERT INTO cities (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        $stmt->execute([':name' => $city['name']]);
        log_event("Seeded city: " . $city['name'], "INFO", ['module' => 'seed']);
    } catch (Exception $e) {
        log_event("Failed to seed city " . $city['name'] . ": " . $e->getMessage(), "ERROR", ['module' => 'seed']);
    }
}

echo "✅ Cities seeded successfully\n";
