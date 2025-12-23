<?php
// backend/tests/cache_health.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId = $_SESSION['user']['id'] ?? null;

try {
    // Check forecast cache
    $stmt = db()->prepare("SELECT updated_at, JSON_LENGTH(payload) AS items 
                           FROM weather_cache 
                           WHERE user_id = ? AND type = 'forecast'");
    $stmt->execute([$userId]);
    $forecast = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check alerts cache
    $stmt = db()->prepare("SELECT updated_at, JSON_LENGTH(payload) AS items 
                           FROM weather_cache 
                           WHERE user_id = ? AND type = 'alerts'");
    $stmt->execute([$userId]);
    $alerts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'       => true,
        'user_id'       => $userId,
        'forecast_cache'=> $forecast ?: 'missing',
        'alerts_cache'  => $alerts ?: 'missing'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    log_event("Cache health check failed: ".$e->getMessage(), "ERROR", ['module'=>'cache_health','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
