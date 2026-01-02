<?php
// Oromia Region Unified API (Shashamane)
// Combines forecast + alerts into one JSON response for radar overlay



require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/alerts.php'; // normalize_alerts

function oromia_api(): array {
    // Forecast
    $forecastData = [];
    try {
        $forecastData = oromia_forecast();
    } catch (Throwable $e) {
        $forecastData = ['city' => 'Shashamane', 'forecast' => [], 'status' => 'FAIL'];
    }

    $forecast = $forecastData['forecast'] ?? [];
    if (empty($forecast)) {
        $forecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 20,
            'max_temp'  => 26,
            'condition' => 'Demo Clear Sky',
            'icon'      => '01d'
        ]];
    }

    // Alerts
    $alertsData = [];
    try {
        $alertsData = oromia_alerts();
    } catch (Throwable $e) {
        $alertsData = ['city' => 'Shashamane', 'alerts' => [], 'data_status' => 'FAIL'];
    }

    $alerts = normalize_alerts($alertsData['alerts'] ?? []);
    if (empty($alerts)) {
        $alerts = [[
            'event'       => 'Demo Flood Warning',
            'description' => 'Heavy rainfall expected in Shashamane region',
            'severity'    => 'severe',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+4 hours')),
            'sender_name' => 'Demo Service'
        ]];
    }

    return [
        'region'   => 'Oromia',
        'city'     => $forecastData['city'] ?? $alertsData['city'] ?? 'Shashamane',
        'forecast' => $forecast,
        'alerts'   => $alerts,
        'status'   => [
            'forecast' => ($forecastData['status'] ?? 'DEMO'),
            'alerts'   => ($alertsData['data_status'] ?? 'DEMO')
        ],
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

// ✅ Only output JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(oromia_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
