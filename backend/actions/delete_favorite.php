<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId = $_SESSION['user']['id'] ?? null;
$cityId = $_POST['city_id'] ?? null;

if (!$userId || !$cityId) {
    http_response_code(400);
    echo json_encode(['status' => 'FAIL', 'message' => 'Missing user or city']);
    exit;
}

try {
    $stmt = db()->prepare("DELETE FROM favorites WHERE user_id=? AND city_id=?");
    $stmt->execute([$userId, $cityId]);

    if ($stmt->rowCount() > 0) {
        log_event("User {$userId} removed city {$cityId}", "INFO", ['module'=>'favorites']);
        echo json_encode(['status' => 'OK', 'message' => 'Favorite removed']);
    } else {
        echo json_encode(['status' => 'INFO', 'message' => 'Favorite not found']);
    }
} catch (Exception $e) {
    log_event("Delete favorite failed: ".$e->getMessage(), "ERROR", ['module'=>'favorites']);
    http_response_code(500);
    echo json_encode(['status' => 'FAIL', 'message' => 'Server error']);
}
