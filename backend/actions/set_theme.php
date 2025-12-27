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
$theme  = $_POST['theme'] ?? null;

// Validate input
if (!$userId || !$theme) {
    http_response_code(400);
    echo json_encode(['status' => 'FAIL', 'message' => 'Missing user or theme']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $userId]);

    log_event("User {$userId} set theme {$theme}", "INFO", ['module' => 'theme']);
    echo json_encode(['status' => 'OK', 'message' => 'Theme updated']);
} catch (Exception $e) {
    log_event("Set theme failed: " . $e->getMessage(), "ERROR", ['module' => 'theme']);
    http_response_code(500);
    echo json_encode(['status' => 'FAIL', 'message' => 'Server error']);
}
