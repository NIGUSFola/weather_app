<?php
// backend/ethiopia_service/regions/oromia/alerts.php

function oromia_alerts(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../../../config/api.php';
    require_once __DIR__ . '/../../../../backend/helpers/weather_api.php'; // shared helpers
    require_once __DIR__ . '/../../../../backend/helpers/log.php';

    $regionName  = 'Oromia';
    $defaultCity = 'Shashamane';
    $city        = $_GET['city'] ?? $defaultCity;

    $status   = 'OK';
    $alerts   = [];
    $forecast = [];

    try {
        $apiKey = $apiConfig['openweathermap'] ?? null;

        if ($apiKey) {
            $alerts   = getAlertsForCity($city, $apiKey)   ?? [];
            $forecast = getForecastForCity($city, $apiKey) ?? [];
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

    // ✅ Unified schema with health info
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

// ✅ Standalone mode
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(oromia_alerts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
