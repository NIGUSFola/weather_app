<?php
// backend/ethiopia_service/forecast.php
// Distributed aggregator: calls region services dynamically via config/app.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../helpers/config.php';   // ✅ use unified config helper
require_once __DIR__ . '/../../config/app.php';    // ✅ service registry
require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user']['id'] ?? null;
$role   = $_SESSION['user']['role'] ?? null;

// ✅ Enforce rate limit only for logged-in accounts
if ($userId) {
    enforce_rate_limit(getRateLimit());
}

// --- Collect forecasts from all registered region services ---
$regions = [];
foreach ($app['services'] as $regionName => $baseUrl) {
    $endpoint = rtrim($baseUrl, '/') . '/forecast.php';
    $status   = 'FAIL';
    $forecast = [];

    try {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5s timeout
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['forecast'])) {
                $forecast = $data['forecast'];
                $status   = $data['status'] ?? 'OK';
            }
        }
    } catch (Exception $e) {
        log_event("Region fetch failed: $regionName - ".$e->getMessage(), "ERROR", [
            'module' => 'aggregator_forecast',
            'region' => $regionName
        ]);
    }

    $regions[$regionName] = [
        'city'     => $regionName,
        'forecast' => $forecast,
        'status'   => $status
    ];
}

// --- Summary: total days of forecast across all regions
$totalDays = 0;
foreach ($regions as $info) {
    $totalDays += is_array($info['forecast']) ? count($info['forecast']) : 0;
}

$summary = [
    'total_days'   => $totalDays,
    'generated_at' => date('Y-m-d H:i:s')
];

// --- Response shaping ---
$response = [
    'summary' => $summary,
    'regions' => $regions
];

// ✅ Add user forecast if logged in as user
if ($userId && $role === 'user') {
    $response['user'] = [
        'regions' => array_map(function($region) {
            return [
                'city'     => $region['city'],
                'forecast' => $region['forecast']
            ];
        }, $regions)
    ];
}

// ✅ Add admin forecast if logged in as admin
if ($userId && $role === 'admin') {
    $response['admin'] = [
        'regions' => array_map(function($region) {
            return [
                'city'     => $region['city'],
                'forecast' => $region['forecast'],
                'meta'     => [
                    'record_count' => is_array($region['forecast']) ? count($region['forecast']) : 0,
                    'status'       => $region['status']
                ]
            ];
        }, $regions),
        'meta' => $summary
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
