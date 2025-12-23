<?php
// weather_app/backend/aggregator/merge_feeds.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../backend/helpers/log.php';

$countryName = 'Ethiopia';

// Region definitions
$regionsConfig = [
    'Oromia' => [
        'alertsPath'   => __DIR__ . '/../ethiopia_service/regions/oromia/alerts.php',
        'forecastPath' => __DIR__ . '/../ethiopia_service/regions/oromia/forecast.php',
        'alertsFn'     => 'oromia_alerts',
        'forecastFn'   => 'oromia_forecast'
    ],
    'South' => [
        'alertsPath'   => __DIR__ . '/../ethiopia_service/regions/south/alerts.php',
        'forecastPath' => __DIR__ . '/../ethiopia_service/regions/south/forecast.php',
        'alertsFn'     => 'south_alerts',
        'forecastFn'   => 'south_forecast'
    ],
    'Amhara' => [
        'alertsPath'   => __DIR__ . '/../ethiopia_service/regions/amhara/alerts.php',
        'forecastPath' => __DIR__ . '/../ethiopia_service/regions/amhara/forecast.php',
        'alertsFn'     => 'amhara_alerts',
        'forecastFn'   => 'amhara_forecast'
    ]
];

// ---------- Helpers ----------
function callRegionFunction(string $fn): ?array {
    try {
        if (!function_exists($fn)) return null;
        $raw = $fn();
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    } catch (Throwable $e) {
        log_event("Aggregator fn failed: " . $e->getMessage(), "ERROR", [
            'module' => 'aggregator',
            'fn'     => $fn
        ]);
        return null;
    }
}

// ---------- Build response ----------
$response = [
    'country'    => $countryName,
    'status'     => 'OK',
    'checked_at' => date('Y-m-d H:i:s'),
    'summary'    => [
        'total_alerts' => 0,
        'regions_up'   => 0,
        'regions_down' => 0,
    ],
    'regions'    => []
];

foreach ($regionsConfig as $regionName => $cfg) {
    $regionInfo = [
        'city'     => null,
        'alerts'   => [],
        'forecast' => [],
        'health'   => [
            'status'     => 'FAIL',
            'checked_at' => date('Y-m-d H:i:s')
        ]
    ];

    // Load alerts
    if (file_exists($cfg['alertsPath'])) {
        require_once $cfg['alertsPath'];
    }
    $alertsData = callRegionFunction($cfg['alertsFn']);

    // Load forecast
    if (file_exists($cfg['forecastPath'])) {
        require_once $cfg['forecastPath'];
    }
    $forecastData = callRegionFunction($cfg['forecastFn']);

    if ($alertsData || $forecastData) {
        $regionInfo['city']     = $alertsData['city'] ?? ($forecastData['city'] ?? null);
        $regionInfo['alerts']   = $alertsData['alerts'] ?? [];
        // ✅ Prefer forecast from alerts if non-empty, otherwise use forecast.php
        $regionInfo['forecast'] = !empty($alertsData['forecast'])
            ? $alertsData['forecast']
            : ($forecastData['forecast'] ?? []);
        $regionInfo['health']['status'] = $alertsData['health']['status'] ?? 'OK';

        $response['summary']['regions_up']++;
        $response['summary']['total_alerts'] += count($regionInfo['alerts']);
    } else {
        $response['summary']['regions_down']++;
        log_event("Aggregator: region unavailable", "WARN", [
            'module' => 'aggregator',
            'region' => $regionName
        ]);
    }

    $response['regions'][$regionName] = $regionInfo;
}

// ---------- Output ----------
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
