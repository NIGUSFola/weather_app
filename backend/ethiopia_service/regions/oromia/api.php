<?php
// backend/ethiopia_service/regions/oromia/api.php

function oromia_api(): string {
    require_once __DIR__ . '/forecast.php';
    require_once __DIR__ . '/alerts.php';

    // Decode forecast safely
    $forecastRaw = oromia_forecast();
    $forecast = json_decode($forecastRaw, true);
    if (!is_array($forecast)) {
        $forecast = ['city' => 'Shashamane', 'forecast' => []];
    }

    // Decode alerts safely
    $alertsRaw = oromia_alerts();
    $alerts = json_decode($alertsRaw, true);
    if (!is_array($alerts)) {
        $alerts = ['alerts' => []];
    }

    // ✅ For testing: inject a sample alert if none exist
    if (empty($alerts['alerts'])) {
        $alerts['alerts'][] = [
            'event'       => 'Thunderstorm',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'description' => 'Severe thunderstorm expected in Shashamane region.',
            'severity'    => 'high'
        ];
    }

    return json_encode([
        'region'   => 'Oromia',
        'city'     => $forecast['city'] ?? 'Shashamane',
        'forecast' => $forecast['forecast'] ?? [],
        'alerts'   => $alerts['alerts'] ?? []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ✅ Standalone mode
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo oromia_api();
    exit;
}
