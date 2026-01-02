<?php
// Addis Ababa forecast service with caching + demo fallback

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function addis_ababa_forecast(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Addis Ababa', 'forecast' => [], 'status' => 'NO_API_KEY'];
    }

    $pdo   = db();
    $city  = 'Addis Ababa';
    $type  = 'forecast';
    $ttl   = 600; // 10 minutes

    // 🔎 Resolve city_id
    $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
    $stmt->execute([$city]);
    $cityRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $cityId  = $cityRow['id'] ?? null;

    if (!$cityId) {
        return ['city' => $city, 'forecast' => [], 'status' => 'NO_CITY_ID'];
    }

    // 1) Check cache
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE city_id = ? AND type = ?");
    $stmt->execute([$cityId, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $age = time() - strtotime($row['updated_at']);
        if ($age < $ttl) {
            $cached = json_decode($row['payload'], true) ?? [];
            if (!empty($cached) && isset($cached[0]['date'])) {
                return ['city' => $city, 'forecast' => $cached, 'status' => 'CACHED'];
            }
        }
    }

    // 2) Fetch from API
    try {
        $forecast = getForecastForCity($city, $apiKey, 'en', 'metric');
        if (is_array($forecast) && count($forecast) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$cityId, $type, json_encode($forecast)]);
            return ['city' => $city, 'forecast' => $forecast, 'status' => 'FRESH'];
        }

        // ✅ Demo fallback if API returns nothing
        $demoForecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 18,
            'max_temp'  => 23,
            'condition' => 'Cloudy with showers',
            'icon'      => '09d'
        ]];
        $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$cityId, $type, json_encode($demoForecast)]);
        return ['city' => $city, 'forecast' => $demoForecast, 'status' => 'DEMO'];
    } catch (Exception $e) {
        // Serve stale cache if available
        if ($row) {
            $cached = json_decode($row['payload'], true) ?? [];
            if (!empty($cached) && isset($cached[0]['date'])) {
                return ['city' => $city, 'forecast' => $cached, 'status' => 'STALE'];
            }
        }
        return ['city' => $city, 'forecast' => [], 'status' => 'FAIL', 'error' => $e->getMessage()];
    }
}

// ✅ Only echo JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = addis_ababa_forecast();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'city'       => 'Addis Ababa',
            'forecast'   => [],
            'status'     => 'FAIL',
            'error'      => $e->getMessage(),
            'checked_at' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
