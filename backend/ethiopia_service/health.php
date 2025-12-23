<?php
// backend/ethiopia_service/health.php
// Unified system health check endpoint

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';    // DB connection
require_once __DIR__ . '/../helpers/log.php';     // Logging helper
require_once __DIR__ . '/../../config/api.php';   // $apiConfig

// --- Health checks ---
$checks = [
    'db'      => false,
    'api_key' => false,
    'session' => false,
    'cache'   => false
];

// 1. Database check
try {
    $pdo = db();
    $pdo->query("SELECT 1");
    $checks['db'] = true;
} catch (Exception $e) {
    log_event("DB health check failed: " . $e->getMessage(), "ERROR");
}

// 2. API key check (basic presence + live test)
$apiKey = $apiConfig['openweathermap'] ?? null;
if ($apiKey) {
    $testUrl = "https://api.openweathermap.org/data/2.5/weather?q=Addis%20Ababa&appid=" . urlencode($apiKey);
    $res = @file_get_contents($testUrl);
    if ($res !== false) {
        $data = json_decode($res, true);
        if (is_array($data) && isset($data['weather'])) {
            $checks['api_key'] = true;
        } else {
            log_event("API key invalid or quota exceeded", "WARN");
        }
    } else {
        log_event("API key check failed (network/API issue)", "ERROR");
    }
} else {
    log_event("API key missing in config", "ERROR");
}

// 3. Session check (active PHP session, not just logged-in user)
$checks['session'] = (session_status() === PHP_SESSION_ACTIVE);

// 4. Cache check (at least one forecast row exists)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM weather_cache WHERE type='forecast'");
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        $checks['cache'] = true;
    } else {
        log_event("No forecast rows found in weather_cache", "WARN");
    }
} catch (Exception $e) {
    log_event("Cache health check failed: " . $e->getMessage(), "ERROR");
}

// --- Overall status ---
$status = ($checks['db'] && $checks['api_key'] && $checks['session'] && $checks['cache']) ? 'ok' : 'degraded';

// ✅ Output JSON only
echo json_encode([
    'status' => $status,
    'checks' => $checks,
    'time'   => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
