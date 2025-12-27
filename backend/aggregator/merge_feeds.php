<?php
// backend/aggregator/merge_feeds.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ✅ Normalization helpers
require_once __DIR__ . '/../helpers/forecast.php';
require_once __DIR__ . '/../helpers/alerts.php';

// ✅ Include each region’s unified API (functions return arrays)
require_once __DIR__ . '/../ethiopia_service/regions/oromia/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/south/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/amhara/api.php';
require_once __DIR__ . '/../ethiopia_service/regions/addis_ababa/api.php';

/**
 * Wrap region API call with health info and normalization
 */
function wrapRegion(string $name, string $fnName): array {
    try {
        if (function_exists($fnName)) {
            $data = call_user_func($fnName); // ✅ safely call by name
            if (is_array($data)) {
                return [
                    'region'   => $name,
                    'city'     => $data['city'] ?? $name,
                    'forecast' => normalize_forecast($data['forecast'] ?? []),
                    'alerts'   => normalize_alerts($data['alerts'] ?? []),
                    'health'   => [
                        'status'     => 'OK',
                        'checked_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Aggregator failed for $name: " . $e->getMessage());
    }

    // Fallback if region API fails
    return [
        'region'   => $name,
        'city'     => $name,
        'forecast' => [],
        'alerts'   => [],
        'health'   => [
            'status'     => 'FAIL',
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
    $totalAlerts += is_array($info['alerts']) ? count($info['alerts']) : 0;
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

exit; // ✅ ensures no stray output
