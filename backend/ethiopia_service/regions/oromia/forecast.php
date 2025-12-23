<?php
// backend/ethiopia_service/regions/oromia/forecast.php

function oromia_forecast(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_reporting(E_ERROR | E_PARSE);

    require_once __DIR__ . '/../../../../config/api.php';
    require_once __DIR__ . '/../../../../backend/helpers/weather_api.php'; // ✅ shared curl_get + getForecastForCity
    require_once __DIR__ . '/../../../../backend/helpers/log.php';

    $regionName   = 'Oromia';
    $defaultCity  = 'Shashamane';
    $city         = $_GET['city'] ?? $defaultCity;

    $status   = 'OK';
    $forecast = [];

    try {
        // ✅ Use global config so aggregator sees the API key
        $apiKey = $GLOBALS['apiConfig']['openweathermap'] ?? null;

        if ($apiKey) {
            $forecast = getForecastForCity($city, $apiKey) ?? [];

            log_event("$regionName forecast served for $city", "INFO", [
                'module'         => 'forecast',
                'region'         => $regionName,
                'city'           => $city,
                'forecast_count' => is_array($forecast) ? count($forecast) : 0
            ]);
        } else {
            $status = 'FAIL';
            log_event("Missing API key for $regionName", "ERROR", [
                'module' => 'forecast',
                'region' => $regionName,
                'city'   => $city
            ]);
        }
    } catch (Throwable $e) {
        $status   = 'FAIL';
        $forecast = [];
        log_event("$regionName forecast failed: " . $e->getMessage(), "ERROR", [
            'module' => 'forecast',
            'region' => $regionName,
            'city'   => $city
        ]);
    }

    // ✅ Unified schema with health info
    return [
        'region'   => $regionName,
        'city'     => $city,
        'forecast' => $forecast,
        'health'   => [
            'status'     => $status,
            'checked_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// ✅ Standalone mode
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(oromia_forecast(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
