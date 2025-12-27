<?php
// backend/ethiopia_service/cache_verify.php
// Verify DB-backed cache entries

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

try {
    $sql = "
        SELECT wc.id,
               c.name AS city,
               wc.type,
               wc.updated_at,
               JSON_LENGTH(wc.payload) AS alert_count
        FROM weather_cache wc
        JOIN cities c ON wc.city_id = c.id
        WHERE wc.type = 'alerts'
        ORDER BY wc.updated_at DESC
        LIMIT 10
    ";
    $stmt = db()->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'count'  => count($rows),
        'cache'  => $rows
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
