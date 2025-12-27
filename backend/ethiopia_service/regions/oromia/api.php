<?php
// Oromia Region API
require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/forecast.php';
require_once __DIR__ . '/../../../helpers/alerts.php';

function oromia_api(): array {
    $forecastData = oromia_forecast();
    $alertsData   = oromia_alerts();

    return [
        'region'   => 'Oromia',
        'city'     => $forecastData['city'] ?? 'Shashamane',
        'forecast' => normalize_forecast($forecastData['forecast'] ?? []),
        'alerts'   => normalize_alerts($alertsData['alerts'] ?? []),
        'status'   => [
            'forecast' => $forecastData['status'] ?? 'UNKNOWN',
            'alerts'   => $alertsData['status'] ?? 'UNKNOWN'
        ]
    ];
}

// ✅ Only output JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(oromia_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
