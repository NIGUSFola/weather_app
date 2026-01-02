<?php
// Addis Ababa Region Unified API
// Combines forecast + alerts into one JSON response for radar overlay

require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/alerts.php'; // normalize_alerts

function addis_ababa_api(): array {
    // Forecast
    $forecastData = [];
    try {
        $forecastData = addis_ababa_forecast();
    } catch (Throwable $e) {
        $forecastData = ['city' => 'Addis Ababa', 'forecast' => [], 'status' => 'FAIL'];
    }

    $forecast = $forecastData['forecast'] ?? [];
    if (empty($forecast)) {
        $forecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 18,
            'max_temp'  => 23,
            'condition' => 'Demo Cloudy',
            'icon'      => '03d'
        ]];
    }

    // Alerts
    $alertsData = [];
    try {
        $alertsData = addis_ababa_alerts();
    } catch (Throwable $e) {
        $alertsData = ['city' => 'Addis Ababa', 'alerts' => [], 'data_status' => 'FAIL'];
    }

    $alerts = normalize_alerts($alertsData['alerts'] ?? []);
    if (empty($alerts)) {
        $alerts = [[
            'event'       => 'Demo Heat Advisory',
            'description' => 'High temperatures expected in Addis Ababa this afternoon',
            'severity'    => 'moderate',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+5 hours')),
            'sender_name' => 'Demo Service'
        ]];
    }

    return [
        'region'     => 'Addis Ababa',
        'city'       => $forecastData['city'] ?? $alertsData['city'] ?? 'Addis Ababa',
        'forecast'   => $forecast,
        'alerts'     => $alerts,
        'status'     => [
            'forecast' => ($forecastData['status'] ?? 'DEMO'),
            'alerts'   => ($alertsData['data_status'] ?? 'DEMO')
        ],
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

// ✅ Only output JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(addis_ababa_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
