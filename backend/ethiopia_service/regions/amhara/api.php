<?php
// Amhara Region Unified API (Bahir Dar)
// Combines forecast + alerts into one JSON response for radar overlay


require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/alerts.php'; // normalize_alerts

function amhara_api(): array {
    // Forecast
    $forecastData = [];
    try {
        $forecastData = amhara_forecast();
    } catch (Throwable $e) {
        $forecastData = ['city' => 'Bahir Dar', 'forecast' => [], 'status' => 'FAIL'];
    }

    $forecast = $forecastData['forecast'] ?? [];
    if (empty($forecast)) {
        $forecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 27,
            'max_temp'  => 33,
            'condition' => 'Demo Clear Sky',
            'icon'      => '01d'
        ]];
    }

    // Alerts
    $alertsData = [];
    try {
        $alertsData = amhara_alerts();
    } catch (Throwable $e) {
        $alertsData = ['city' => 'Bahir Dar', 'alerts' => [], 'data_status' => 'FAIL'];
    }

    $alerts = normalize_alerts($alertsData['alerts'] ?? []);
    if (empty($alerts)) {
        $alerts = [[
            'event'       => 'Demo Storm Warning',
            'description' => 'Strong winds expected in Bahir Dar region',
            'severity'    => 'moderate',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'sender_name' => 'Demo Service'
        ]];
    }

    return [
        'region'     => 'Amhara',
        'city'       => $forecastData['city'] ?? $alertsData['city'] ?? 'Bahir Dar',
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
    echo json_encode(amhara_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
