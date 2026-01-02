<?php
// backend/aggregator/merge_feeds.php
// Unified aggregator: merges all region APIs into one JSON feed

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ✅ Include helpers (for demo fallback only)
require_once __DIR__ . '/../helpers/alerts.php';
require_once __DIR__ . '/../helpers/forecast.php';

// ✅ Include each region’s unified API
require_once __DIR__ . '/../ethiopia_service/regions/oromia/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/south/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/amhara/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/addis_ababa/api.php';

/**
 * Wrap region API call with health info
 */
function wrapRegion(string $name, string $fnName): array {
    try {
        if (function_exists($fnName)) {
            $data = call_user_func($fnName);
            if (is_array($data)) {
                // Handle both string and array status safely
                $statusBlock = $data['status'] ?? 'UNKNOWN';
                $forecastStatus = is_array($statusBlock) ? ($statusBlock['forecast'] ?? 'UNKNOWN') : $statusBlock;
                $alertsStatus   = is_array($statusBlock) ? ($statusBlock['alerts'] ?? 'UNKNOWN') : 'UNKNOWN';

                return [
                    'region'   => $name,
                    'city'     => $data['city'] ?? $name,
                    'forecast' => $data['forecast'] ?? [],
                    'alerts'   => $data['alerts'] ?? [],
                    'health'   => [
                        'status'     => $forecastStatus,
                        'alerts'     => $alertsStatus,
                        'checked_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }
        }
    } catch (Throwable $e) {
        error_log("Aggregator failed for $name: " . $e->getMessage());
    }

    // Essential fallback
    return [
        'region'   => $name,
        'city'     => $name,
        'forecast' => [],
        'alerts'   => [[
            'event'       => "Demo Alert for $name",
            'description' => "Simulated severe weather in $name",
            'severity'    => 'moderate',
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'sender_name' => 'Demo Service'
        ]],
        'health'   => [
            'status'     => 'DEMO',
            'alerts'     => 'DEMO',
            'checked_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// ✅ Build unified regions array
$regions = [
    'Oromia'      => wrapRegion('Oromia', 'oromia_api'),
    'South'       => wrapRegion('South', 'south_api'),
    'Amhara'      => wrapRegion('Amhara', 'amhara_api'),
    'Addis Ababa' => wrapRegion('Addis Ababa', 'addis_ababa_api'),
];

// ✅ Summary: total active alerts across all regions
$totalAlerts = 0;
foreach ($regions as $info) {
    if (!empty($info['alerts']) && is_array($info['alerts'])) {
        $totalAlerts += count($info['alerts']);
    }
}

$summary = [
    'total_alerts' => $totalAlerts,
    'generated_at' => date('Y-m-d H:i:s')
];

// ✅ Output unified JSON
echo json_encode([
    'summary' => $summary,
    'regions' => $regions
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

exit;
