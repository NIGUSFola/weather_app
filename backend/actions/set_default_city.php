<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';

// Ensure user is logged in
require_user();

$userId = $_SESSION['user']['id'] ?? null;
$cityId = $_POST['city_id'] ?? null;

// Validate input
if (!$userId || !$cityId) {
    http_response_code(400);
    echo json_encode(['status' => 'FAIL', 'message' => 'Missing user or city']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE users SET default_city_id = ? WHERE id = ?");
    $stmt->execute([$cityId, $userId]);

    log_event("User {$userId} set default city {$cityId}", "INFO", ['module' => 'favorites']);
    echo json_encode(['status' => 'OK', 'message' => 'Default city updated']);
} catch (Exception $e) {
    log_event("Set default city failed: " . $e->getMessage(), "ERROR", ['module' => 'favorites']);
    http_response_code(500);
    echo json_encode(['status' => 'FAIL', 'message' => 'Server error']);
}
