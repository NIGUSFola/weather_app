<?php
// backend/ethiopia_service/admin/config_api.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../../config/db.php';

require_admin();
header('Content-Type: application/json');

$csrfToken = generate_csrf_token();
// CSRF check helper
function check_csrf(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

try {
    if ($action === 'get') {
        // Load settings from DB
        $stmt = db()->query("SELECT setting_key, setting_value FROM system_config");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        echo json_encode(['settings' => $settings], JSON_PRETTY_PRINT);
        exit;
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!check_csrf()) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $apiKey        = trim($_POST['apiKey'] ?? '');
        $cacheDuration = (int)($_POST['cacheDuration'] ?? 0);
        $rateLimit     = (int)($_POST['rateLimit'] ?? 0);

        if ($apiKey === '' || $cacheDuration <= 0 || $rateLimit <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid input values']);
            exit;
        }

        // Update settings in DB (upsert)
        $stmt = db()->prepare("
            INSERT INTO system_config (setting_key, setting_value) VALUES
                ('openweathermap', :apiKey),
                ('cacheDuration', :cacheDuration),
                ('rateLimit', :rateLimit)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([
            ':apiKey'        => $apiKey,
            ':cacheDuration' => $cacheDuration,
            ':rateLimit'     => $rateLimit
        ]);

        log_event("System config updated", "INFO", ['module'=>'config','admin'=>$_SESSION['user']['id']]);
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    log_event("Config API error: " . $e->getMessage(), "ERROR", ['module'=>'config']);
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
