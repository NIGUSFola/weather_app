<?php
// backend/ethiopia_service/regions/south/api.php

function south_api(): array {
    require_once __DIR__ . '/forecast.php';
    require_once __DIR__ . '/alerts.php';

    // Call forecast directly (already returns array)
    $forecast = south_forecast();
    if (!is_array($forecast)) {
        $forecast = ['city' => 'Hawassa', 'forecast' => [], 'health' => ['status'=>'FAIL','checked_at'=>date('Y-m-d H:i:s')]];
    }

    // Call alerts directly (already returns array)
    $alerts = south_alerts();
    if (!is_array($alerts)) {
        $alerts = ['alerts' => [], 'health' => ['status'=>'FAIL','checked_at'=>date('Y-m-d H:i:s')]];
    }

    // ✅ For testing: inject a sample alert if none exist
    if (empty($alerts['alerts'])) {
        $alerts['alerts'][] = [
            'event'       => 'Flood Warning',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+4 hours')),
            'description' => 'Flooding expected in Hawassa region due to heavy rainfall.',
            'severity'    => 'moderate'
        ];
    }

    // ✅ Unified schema
    return [
        'region'   => 'South',
        'city'     => $forecast['city'] ?? 'Hawassa',
        'forecast' => $forecast['forecast'] ?? [],
        'alerts'   => $alerts['alerts'] ?? [],
        'health'   => [
            'status'     => $forecast['health']['status'] ?? $alerts['health']['status'] ?? 'OK',
            'checked_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// ✅ Standalone mode
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(south_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
