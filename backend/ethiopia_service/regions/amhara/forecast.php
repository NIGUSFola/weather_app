<?php
// backend/ethiopia_service/regions/amhara/forecast.php

function amhara_forecast(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_reporting(E_ERROR | E_PARSE);

    require_once __DIR__ . '/../../../../config/api.php';
    require_once __DIR__ . '/../../../../backend/helpers/weather_api.php';
    require_once __DIR__ . '/../../../../backend/helpers/log.php';

    $regionName = 'Amhara';
    $city       = 'Bahir Dar';
    $lat        = 11.5949663;
    $lon        = 37.3882251;

    $status   = 'OK';
    $forecast = [];

    try {
        // ✅ Use global config so aggregator sees the API key
        $apiKey = $GLOBALS['apiConfig']['openweathermap'] ?? null;

        if ($apiKey) {
            $forecast = getForecastByCoords($lat, $lon, $apiKey) ?? [];

            log_event("$regionName forecast served for $city", "INFO", [
                'module'         => 'forecast',
                'region'         => $regionName,
                'city'           => $city,
                'forecast_count' => is_array($forecast) ? count($forecast) : 0
            ]);
        } else {
            $status = 'FAIL';
            log_event("Missing API key for $regionName forecast", "ERROR", [
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
    echo json_encode(amhara_forecast(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
