<?php
// backend/ethiopia_service/regions/south/alerts.php

function south_alerts(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../../../config/api.php';
    require_once __DIR__ . '/../../../../backend/helpers/weather_api.php';
    require_once __DIR__ . '/../../../../backend/helpers/log.php';

    $regionName = 'South';
    $city       = 'Hawassa';
    $lat        = 7.0481029;
    $lon        = 38.47861;

    $status   = 'OK';
    $alerts   = [];
    $forecast = [];

    try {
        $apiKey = $GLOBALS['apiConfig']['openweathermap'] ?? null;

        if ($apiKey) {
            $alerts   = getAlertsByCoords($lat, $lon, $apiKey)   ?? [];
            $forecast = getForecastByCoords($lat, $lon, $apiKey) ?? [];
        } else {
            $status = 'FAIL';
            log_event("Missing API key for $regionName", "ERROR", [
                'module' => 'alerts',
                'region' => $regionName,
                'city'   => $city
            ]);
        }

        log_event("$regionName alerts served for $city", "INFO", [
            'module' => 'alerts',
            'region' => $regionName,
            'city'   => $city
        ]);
    } catch (Throwable $e) {
        $status   = 'FAIL';
        $alerts   = [];
        $forecast = [];
        log_event("$regionName alerts failed: " . $e->getMessage(), "ERROR", [
            'module' => 'alerts',
            'region' => $regionName,
            'city'   => $city
        ]);
    }

    return [
        'region'   => $regionName,
        'city'     => $city,
        'alerts'   => $alerts,
        'forecast' => $forecast,
        'health'   => [
            'status'     => $status,
            'checked_at' => date('Y-m-d H:i:s')
        ]
    ];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(south_alerts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
