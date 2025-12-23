<?php
// backend/ethiopia_service/regions/amhara/api.php

function amhara_api(): string {
    require_once __DIR__ . '/forecast.php';
    require_once __DIR__ . '/alerts.php';

    // Decode forecast safely
    $forecastRaw = amhara_forecast();
    $forecast = json_decode($forecastRaw, true);
    if (!is_array($forecast)) {
        $forecast = ['city' => 'Bahir Dar', 'forecast' => []];
    }

    // Decode alerts safely
    $alertsRaw = amhara_alerts();
    $alerts = json_decode($alertsRaw, true);
    if (!is_array($alerts)) {
        $alerts = ['alerts' => []];
    }

    // ✅ For testing: inject a sample alert if none exist
    if (empty($alerts['alerts'])) {
        $alerts['alerts'][] = [
            'event'       => 'Heavy Rain',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'description' => 'Expected heavy rainfall in Bahir Dar region.',
            'severity'    => 'moderate'
        ];
    }

    return json_encode([
        'region'   => 'Amhara',
        'city'     => $forecast['city'] ?? 'Bahir Dar',
        'forecast' => $forecast['forecast'] ?? [],
        'alerts'   => $alerts['alerts'] ?? []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ✅ Standalone mode
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo amhara_api();
    exit;
}
