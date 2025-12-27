<?php
// backend/ethiopia_service/regions/amhara/api.php
require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/forecast.php';
require_once __DIR__ . '/../../../helpers/alerts.php';

function amhara_api(): array {
    $forecastData = amhara_forecast();
    $alertsData   = amhara_alerts();

    return [
        'region'   => 'Amhara',
        'city'     => $forecastData['city'] ?? 'Bahir Dar',
        'forecast' => normalize_forecast($forecastData['forecast'] ?? []),
        'alerts'   => normalize_alerts($alertsData['alerts'] ?? []),
        'status'   => [
            'forecast' => $forecastData['data_status'] ?? $forecastData['status'] ?? 'UNKNOWN',
            'alerts'   => $alertsData['data_status'] ?? $alertsData['status'] ?? 'UNKNOWN'
        ]
    ];
}

// ✅ Only output JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(amhara_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
