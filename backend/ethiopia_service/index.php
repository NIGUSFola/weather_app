<?php
// backend/ethiopia_service/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
$apiConfig = require __DIR__ . '/../../config/api.php';

require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';

require_user(); // enforce login

// Enforce per-minute rate limit
enforce_rate_limit($apiConfig['rateLimit'] ?? 100);

// --- Input ---
$endpoint = $_GET['endpoint'] ?? '';
$city     = $_GET['city'] ?? ($_POST['city'] ?? 'Addis Ababa');

if ($endpoint === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint parameter required']);
    exit;
}

// --- Router ---
try {
    switch (strtolower($endpoint)) {
        case 'forecast':
            require __DIR__ . '/forecast.php';
            break;

        case 'alerts':
            require __DIR__ . '/alerts.php';
            break;

        case 'radar':
            require __DIR__ . '/radar.php';
            break;

        case 'health':
            require __DIR__ . '/health.php';
            break;

        default:
            log_event("Invalid endpoint requested", "WARN", ['module'=>'index','endpoint'=>$endpoint]);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
} catch (Exception $e) {
    log_event("Index router error: " . $e->getMessage(), "ERROR", ['module'=>'index','endpoint'=>$endpoint]);
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
