<?php
// backend/ethiopia_service/regions/oromia/forecast.php
// Oromia forecast service (Shashamane) with caching + configurable TTL + demo fallback (aligned to city_id schema)

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function oromia_forecast(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Shashamane', 'forecast' => [], 'status' => 'NO_API_KEY'];
    }

    $pdo   = db();
    $city  = 'Shashamane';
    $type  = 'forecast';

    // 🔧 Get TTL from system_settings (default 10 minutes if not set)
    $ttl = 600; // fallback
    try {
        $stmt = $pdo->query("SELECT cache_duration FROM system_settings LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ttl = max(60, (int)$row['cache_duration'] * 60);
        }
    } catch (Exception $e) {
        // ignore errors, fallback to default TTL
    }

    // 🔎 Resolve city_id from cities table
    $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
    $stmt->execute([$city]);
    $cityRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $cityId  = $cityRow['id'] ?? null;

    if (!$cityId) {
        return ['city' => $city, 'forecast' => [], 'status' => 'NO_CITY_ID'];
    }

    // 1. Check cache by city_id + type
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE city_id = ? AND type = ?");
    $stmt->execute([$cityId, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $age = time() - strtotime($row['updated_at']);
        if ($age < $ttl) {
            return [
                'city'     => $city,
                'forecast' => json_decode($row['payload'], true) ?? [],
                'status'   => 'CACHED'
            ];
        }
    }

    // 2. Fetch from API
    try {
        $forecast = getForecastForCity($city, $apiKey, 'en', 'metric');
        if (is_array($forecast) && count($forecast) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$cityId, $type, json_encode($forecast)]);
            return ['city' => $city, 'forecast' => $forecast, 'status' => 'FRESH'];
        }

        // ✅ Demo fallback if no forecast
        $demoForecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 20,
            'max_temp'  => 26,
            'condition' => 'Partly cloudy',
            'icon'      => '03d'
        ]];
        $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$cityId, $type, json_encode($demoForecast)]);
        return ['city' => $city, 'forecast' => $demoForecast, 'status' => 'DEMO'];
    } catch (Exception $e) {
        if ($row) {
            return [
                'city'     => $city,
                'forecast' => json_decode($row['payload'], true) ?? [],
                'status'   => 'STALE'
            ];
        }
        return [
            'city'     => $city,
            'forecast' => [],
            'status'   => 'FAIL',
            'error'    => $e->getMessage()
        ];
    }
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
