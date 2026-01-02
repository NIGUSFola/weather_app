<?php
// backend/ethiopia_service/api.php
// REST-style API key management for Ethiopia Weather

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

header('Content-Type: application/json; charset=utf-8');

// ✅ Ensure user is logged in
$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

// ✅ Ensure CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ CSRF check helper
function check_csrf(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ✅ Determine action
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

try {
    switch ($action) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Invalid request method']);
                exit;
            }
            if (!check_csrf()) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }

            // Generate new API key
            $newKey  = bin2hex(random_bytes(16));
            $keyName = $_POST['key_name'] ?? ("User Key " . date('Y-m-d H:i:s'));

            $stmt = db()->prepare("
                INSERT INTO api_keys (user_id, key_name, api_key, created_at)
                VALUES (:user_id, :key_name, :api_key, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':key_name' => $keyName,
                ':api_key' => $newKey
            ]);

            log_event("API key created", "INFO", [
                'module'   => 'api',
                'user_id'  => $userId,
                'key_name' => $keyName
            ]);

            echo json_encode([
                'success'    => true,
                'message'    => 'API key generated',
                'api_key'    => $newKey,
                'key_name'   => $keyName,
                'csrf_token' => $_SESSION['csrf_token']
            ]);
            exit;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Invalid request method']);
                exit;
            }
            if (!check_csrf()) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }

            $key = $_POST['key'] ?? '';
            if (!$key) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing API key']);
                exit;
            }

            $stmt = db()->prepare("DELETE FROM api_keys WHERE user_id = :user_id AND api_key = :api_key");
            $stmt->execute([':user_id' => $userId, ':api_key' => $key]);

            log_event("API key deleted", "INFO", [
                'module'   => 'api',
                'user_id'  => $userId,
                'api_key'  => $key
            ]);

            echo json_encode(['success' => true, 'message' => 'API key deleted']);
            exit;

        case 'list':
        default:
            $stmt = db()->prepare("SELECT key_name, api_key, created_at 
                                   FROM api_keys 
                                   WHERE user_id = :user_id 
                                   ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $userId]);
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'keys'       => $keys,
                'csrf_token' => $_SESSION['csrf_token']
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }
} catch (Exception $e) {
    log_event("API action failed: " . $e->getMessage(), "ERROR", [
        'module'  => 'api',
        'user_id' => $userId,
        'action'  => $action
    ]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}
