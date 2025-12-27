<?php
// backend/ethiopia_service/regions/addis_ababa/api.php
require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/forecast.php';
require_once __DIR__ . '/../../../helpers/alerts.php';

function addis_ababa_api(): array {
    $forecastData = addis_ababa_forecast();
    $alertsData   = addis_ababa_alerts();

    return [
        'region'   => 'Addis Ababa',
        'city'     => $forecastData['city'] ?? 'Addis Ababa',
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
    echo json_encode(addis_ababa_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
