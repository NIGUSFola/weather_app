<?php
// backend/ethiopia_service/health.php
// Unified system health check endpoint

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';
$apiConfig = require __DIR__ . '/../../config/api.php';

$checks = [
    'db'          => false,
    'api_key'     => false,
    'session'     => false,
    'cache'       => false,
    'amhara'      => false,
    'oromia'      => false,
    'south'       => false,
    'addis_ababa' => false
];

function curl_get(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res === false) return null;
    $json = json_decode($res, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $json : null;
}

// 1. Database check
try {
    $pdo = db();
    $pdo->query("SELECT 1");
    $checks['db'] = true;
} catch (Exception $e) {
    log_event("DB health check failed: " . $e->getMessage(), "ERROR");
}

// 2. API key check
$apiKey = $apiConfig['openweathermap'] ?? null;
if ($apiKey) {
    $testUrl = "https://api.openweathermap.org/data/2.5/weather?q=Addis%20Ababa&appid=" . urlencode($apiKey);
    $res = curl_get($testUrl);
    $checks['api_key'] = ($res && isset($res['cod']) && (int)$res['cod'] === 200);
} else {
    log_event("API key missing in config", "ERROR");
}

// 3. Session check
$checks['session'] = (session_status() === PHP_SESSION_ACTIVE);

// 4. Cache check
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM weather_cache");
    $row  = $stmt->fetchColumn();
    $checks['cache'] = ($row !== false);
} catch (Exception $e) {
    log_event("Cache health check failed: " . $e->getMessage(), "ERROR");
}

// 5. Region checks (call each region’s health.php)
foreach (['amhara','oromia','south','addis_ababa'] as $key) {
    $res = curl_get("http://localhost/weather/backend/ethiopia_service/regions/$key/health.php");
    if ($res && isset($res['status']) && strtoupper($res['status']) === 'OK') {
        $checks[$key] = true;
    }
}

// ✅ Overall status
$status = (in_array(false, $checks, true)) ? 'FAIL' : 'OK';

echo json_encode([
    'status'     => $status,
    'checks'     => $checks,
    'time'       => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
