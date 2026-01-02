<?php
// South Region Unified API (Hawassa)
// Combines forecast + alerts into one JSON response for radar overlay


require_once __DIR__ . '/forecast.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/../../../helpers/alerts.php'; // normalize_alerts

function south_api(): array {
    // Forecast
    $forecastData = [];
    try {
        $forecastData = south_forecast();
    } catch (Throwable $e) {
        $forecastData = ['city' => 'Hawassa', 'forecast' => [], 'status' => 'FAIL'];
    }

    $forecast = $forecastData['forecast'] ?? [];
    if (empty($forecast)) {
        $forecast = [[
            'date'      => date('Y-m-d'),
            'min_temp'  => 22,
            'max_temp'  => 28,
            'condition' => 'Demo Cloudy',
            'icon'      => '03d'
        ]];
    }

    // Alerts
    $alertsData = [];
    try {
        $alertsData = south_alerts();
    } catch (Throwable $e) {
        $alertsData = ['city' => 'Hawassa', 'alerts' => [], 'data_status' => 'FAIL'];
    }

    $alerts = normalize_alerts($alertsData['alerts'] ?? []);
    if (empty($alerts)) {
        $alerts = [[
            'event'       => 'Demo Thunderstorm Warning',
            'description' => 'Heavy rain expected in Hawassa until evening',
            'severity'    => 'severe',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+6 hours')),
            'sender_name' => 'Demo Service'
        ]];
    }

    return [
        'region'     => 'South',
        'city'       => $forecastData['city'] ?? $alertsData['city'] ?? 'Hawassa',
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
    echo json_encode(south_api(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
