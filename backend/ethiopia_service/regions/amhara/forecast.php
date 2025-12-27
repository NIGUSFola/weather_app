<?php
// backend/ethiopia_service/regions/amhara/forecast.php
// Amhara forecast service with caching + consistent health reporting

require_once __DIR__ . '/../../../helpers/weather_api.php'; 
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function amhara_forecast(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Bahir Dar', 'forecast' => [], 'data_status' => 'NO_API_KEY'];
    }

    $pdo = db();
    $region = 'Amhara';
    $type   = 'forecast';

    // 1. Check cache (TTL = 10 minutes)
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE region=? AND type=?");
    $stmt->execute([$region, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $age = time() - strtotime($row['updated_at']);
        if ($age < 600) {
            return [
                'city'        => 'Bahir Dar',
                'forecast'    => json_decode($row['payload'], true) ?? [],
                'data_status' => 'CACHED'
            ];
        }
    }

    // 2. Fetch from API
    try {
        $forecast = getForecastForCity('Bahir Dar', $apiKey, 'en', 'metric');
        if ($forecast) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache(region,type,payload,updated_at) VALUES(?,?,?,NOW())");
            $stmt->execute([$region, $type, json_encode($forecast)]);
            return ['city' => 'Bahir Dar', 'forecast' => $forecast, 'data_status' => 'FRESH'];
        }
        // If API returns empty, still healthy
        return ['city' => 'Bahir Dar', 'forecast' => [], 'data_status' => 'NO_DATA'];
    } catch (Exception $e) {
        if ($row) {
            return [
                'city'        => 'Bahir Dar',
                'forecast'    => json_decode($row['payload'], true) ?? [],
                'data_status' => 'STALE'
            ];
        }
        return [
            'city'        => 'Bahir Dar',
            'forecast'    => [],
            'data_status' => 'FAIL',
            'error'       => $e->getMessage()
        ];
    }
}

// ✅ Only echo JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = amhara_forecast();
        $response = [
            'region'      => 'Amhara',
            'city'        => $result['city'],
            'forecast'    => $result['forecast'] ?? [],
            'status'      => 'OK',                  // always OK if service responds
            'data_status' => $result['data_status'] ?? 'UNKNOWN',
            'checked_at'  => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'region'      => 'Amhara',
            'city'        => 'Bahir Dar',
            'forecast'    => [],
            'status'      => 'FAIL',
            'data_status' => 'FAIL',
            'error'       => $e->getMessage(),
            'checked_at'  => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
