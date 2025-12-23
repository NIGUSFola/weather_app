<?php
// backend/actions/add_favorite.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId = $_SESSION['user']['id'] ?? null;
$cityId = $_POST['city_id'] ?? null;

if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

if (!$cityId) {
    http_response_code(400);
    echo json_encode(['error' => 'City ID required']);
    exit;
}

try {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $stmt = db()->prepare("SELECT name FROM cities WHERE id = ?");
    $stmt->execute([$cityId]);
    $cityName = $stmt->fetchColumn();

    if (!$cityName) {
        http_response_code(404);
        echo json_encode(['error' => 'City not found']);
        exit;
    }

    $stmt = db()->prepare("INSERT IGNORE INTO favorites (user_id, city_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $cityId]);

    if ($stmt->rowCount() > 0) {
        log_event("User {$userId} added {$cityName} to favorites", "INFO", ['module'=>'favorites','user_id'=>$userId]);
        echo json_encode(['success' => true, 'message' => "{$cityName} added to favorites"]);
    } else {
        echo json_encode(['error' => "{$cityName} is already in favorites"]);
    }
} catch (Exception $e) {
    log_event("Add favorite failed: " . $e->getMessage(), "ERROR", ['module'=>'favorites','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
