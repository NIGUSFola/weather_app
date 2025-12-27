<?php
// backend/ethiopia_service/regions/oromia/forecast.php
// Oromia forecast service with caching + configurable TTL

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function oromia_forecast(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Shashamane', 'forecast' => [], 'status' => 'NO_API_KEY'];
    }

    $pdo = db();
    $region = 'Oromia';
    $type   = 'forecast';

    // 🔧 Get TTL from system_settings (default 10 minutes if not set)
    $ttl = 600; // fallback
    try {
        $stmt = $pdo->query("SELECT cache_duration FROM system_settings LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ttl = max(60, (int)$row['cache_duration'] * 60); // enforce minimum 1 minute
        }
    } catch (Exception $e) {
        // ignore errors, fallback to default TTL
    }

    // 1. Check cache
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE region=? AND type=?");
    $stmt->execute([$region, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $age = time() - strtotime($row['updated_at']);
        if ($age < $ttl) {
            return [
                'city'     => 'Shashamane',
                'forecast' => json_decode($row['payload'], true),
                'status'   => 'CACHED'
            ];
        }
    }

    // 2. Fetch from API
    try {
        $forecast = getForecastForCity('Shashamane', $apiKey, 'en', 'metric');
        if ($forecast) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache(region,type,payload,updated_at) VALUES(?,?,?,NOW())");
            $stmt->execute([$region, $type, json_encode($forecast)]);
            return ['city' => 'Shashamane', 'forecast' => $forecast, 'status' => 'OK'];
        }
    } catch (Exception $e) {
        return ['city' => 'Shashamane', 'forecast' => [], 'status' => 'FAIL', 'error' => $e->getMessage()];
    }

    // 3. Fallback to stale cache
    if ($row) {
        return [
            'city'     => 'Shashamane',
            'forecast' => json_decode($row['payload'], true),
            'status'   => 'STALE'
        ];
    }

    return ['city' => 'Shashamane', 'forecast' => [], 'status' => 'FAIL'];
}

// ✅ Only echo JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = oromia_forecast();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'city'       => 'Shashamane',
            'forecast'   => [],
            'status'     => 'FAIL',
            'error'      => $e->getMessage(),
            'checked_at' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
