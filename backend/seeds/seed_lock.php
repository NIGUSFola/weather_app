<?php
// backend/seeds/seed_lock.php
// Seed initial distributed lock row for cache refresh

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

$pdo = db();

try {
    $stmt = $pdo->prepare("
        INSERT INTO distributed_locks (name, acquired_at)
        VALUES ('cache_refresh', NOW())
        ON DUPLICATE KEY UPDATE acquired_at = VALUES(acquired_at)
    ");
    $stmt->execute();

    log_event("Seeded distributed lock: cache_refresh", "INFO", ['module' => 'seed']);
    echo "✅ Distributed lock seeded successfully\n";
} catch (Exception $e) {
    log_event("Failed to seed distributed lock: " . $e->getMessage(), "ERROR", ['module' => 'seed']);
    echo "❌ Failed to seed distributed lock\n";
}
