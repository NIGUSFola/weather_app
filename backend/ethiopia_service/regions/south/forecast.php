<?php
// backend/ethiopia_service/regions/south/forecast.php
// South region forecast service (Hawassa) with caching + demo fallback (aligned to weather_cache city_id schema)

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function south_forecast(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Hawassa', 'forecast' => [], 'status' => 'NO_API_KEY'];
    }

    $pdo   = db();
    $city  = 'Hawassa';
    $type  = 'forecast';
    $ttl   = 600; // 10 minutes

    // 🔎 Resolve city_id from cities table
    $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
    $stmt->execute([$city]);
    $cityRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $cityId  = $cityRow['id'] ?? null;

    if (!$cityId) {
        // If city is missing from DB, return a clear status
        return ['city' => $city, 'forecast' => [], 'status' => 'NO_CITY_ID'];
    }

    // 1) Check cache by city_id + type (TTL = 10 minutes)
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE city_id = ? AND type = ?");
    $stmt->execute([$cityId, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (time() - strtotime($row['updated_at']) < $ttl)) {
        return [
            'city'     => $city,
            'forecast' => json_decode($row['payload'], true) ?? [],
            'status'   => 'CACHED'
        ];
    }

    // 2) Fetch from API
    try {
        $forecast = getForecastForCity($city, $apiKey, 'en', 'metric');
        if (is_array($forecast) && count($forecast) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$cityId, $type, json_encode($forecast)]);
            return ['city' => $city, 'forecast' => $forecast, 'status' => 'FRESH'];
        }

        // ✅ Demo fallback if no forecast (daily summary structure)
        $demoForecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 22,
            'max_temp'  => 28,
            'condition' => 'Sunny intervals',
            'icon'      => '01d'
        ]];

        $stmt = $pdo->prepare("REPLACE INTO weather_cache (city_id, type, payload, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$cityId, $type, json_encode($demoForecast)]);
        return ['city' => $city, 'forecast' => $demoForecast, 'status' => 'DEMO'];
    } catch (Exception $e) {
        // Serve stale cache if available
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
        $result = south_forecast();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'city'       => 'Hawassa',
            'forecast'   => [],
            'status'     => 'FAIL',
            'error'      => $e->getMessage(),
            'checked_at' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
