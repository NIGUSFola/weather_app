<?php
// backend/tests/distributed_lock.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

header('Content-Type: application/json');

$lockName = 'cron_lock';

try {
    // Try to acquire lock using INSERT ... ON DUPLICATE KEY UPDATE
    $stmt = db()->prepare("
        INSERT INTO distributed_locks (name, acquired_at) 
        VALUES (:name, NOW())
        ON DUPLICATE KEY UPDATE acquired_at = VALUES(acquired_at)
    ");
    $stmt->execute([':name' => $lockName]);

    echo json_encode([
        'success' => true,
        'message' => 'Lock acquired',
        'lock'    => $lockName
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    log_event("Distributed lock failed: ".$e->getMessage(), "ERROR", ['module'=>'distributed_lock']);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error'
    ], JSON_PRETTY_PRINT);
}
