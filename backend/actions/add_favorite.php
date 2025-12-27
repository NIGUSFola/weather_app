<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId = $_SESSION['user']['id'] ?? null;
$cityId = $_POST['city_id'] ?? null;

if (!$userId || !$cityId) {
    http_response_code(400);
    echo json_encode(['status' => 'FAIL', 'message' => !$userId ? 'User not authenticated' : 'City ID required']);
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'FAIL', 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    $stmt = db()->prepare("SELECT name FROM cities WHERE id=?");
    $stmt->execute([$cityId]);
    $cityName = $stmt->fetchColumn();

    if (!$cityName) {
        http_response_code(404);
        echo json_encode(['status' => 'FAIL', 'message' => 'City not found']);
        exit;
    }

    $stmt = db()->prepare("INSERT IGNORE INTO favorites(user_id, city_id, created_at) VALUES(?,?,NOW())");
    $stmt->execute([$userId, $cityId]);

    if ($stmt->rowCount() > 0) {
        log_event("User {$userId} added {$cityName}", "INFO", ['module'=>'favorites']);
        echo json_encode(['status' => 'OK', 'message' => "{$cityName} added to favorites"]);
    } else {
        echo json_encode(['status' => 'INFO', 'message' => "{$cityName} already in favorites"]);
    }
} catch (Exception $e) {
    log_event("Add favorite failed: ".$e->getMessage(), "ERROR", ['module'=>'favorites']);
    http_response_code(500);
    echo json_encode(['status' => 'FAIL', 'message' => 'Server error']);
}
