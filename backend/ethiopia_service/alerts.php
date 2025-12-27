<?php
// backend/ethiopia_service/alerts.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ✅ Core helpers
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../helpers/alerts.php'; // defines normalize_alerts()

// ✅ Include each region’s alerts API
require_once __DIR__ . '/regions/oromia/alerts.php';
require_once __DIR__ . '/regions/south/alerts.php';
require_once __DIR__ . '/regions/amhara/alerts.php';
require_once __DIR__ . '/regions/addis_ababa/alerts.php';

/**
 * Wrap region alerts call with health info and normalization
 */
function wrapRegionAlerts(string $name, string $city, callable $fn): array {
    try {
        $data = $fn(); // region alerts API returns array
        if (is_array($data)) {
            return [
                'region'     => $name,
                'city'       => $city,
                'alerts'     => normalize_alerts($data['alerts'] ?? []),
                'status'     => $data['status'] ?? 'OK',
                'checked_at' => date('Y-m-d H:i:s')
            ];
        }
    } catch (Exception $e) {
        log_event(
            "Alerts failed for $name: " . $e->getMessage(),
            "ERROR",
            ['module' => 'alerts', 'region' => $name]
        );
    }

    // Fallback if region API fails
    return [
        'region'     => $name,
        'city'       => $city,
        'alerts'     => [],
        'status'     => 'FAIL',
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

// ✅ Build unified regions array
$regions = [
    'Oromia'      => wrapRegionAlerts('Oromia', 'Shashamane', 'oromia_alerts'),
    'South'       => wrapRegionAlerts('South', 'Hawassa', 'south_alerts'),
    'Amhara'      => wrapRegionAlerts('Amhara', 'Bahir Dar', 'amhara_alerts'),
    'Addis Ababa' => wrapRegionAlerts('Addis Ababa', 'Addis Ababa', 'addis_ababa_alerts'),
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
echo json_encode(
    [
        'summary' => $summary,
        'regions' => $regions
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);

exit;
