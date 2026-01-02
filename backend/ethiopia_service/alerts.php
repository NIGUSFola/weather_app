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
                'region'      => $name,
                'city'        => $city,
                'alerts'      => normalize_alerts($data['alerts'] ?? []),
                'status'      => $data['data_status'] ?? (empty($data['alerts']) ? 'NO_ALERTS' : 'OK'),
                'checked_at'  => date('Y-m-d H:i:s')
            ];
        }
    } catch (Throwable $e) {
        log_event(
            "Alerts failed for $name: " . $e->getMessage(),
            "ERROR",
            ['module' => 'alerts', 'region' => $name]
        );
    }

    // ✅ Essential demo fallback if region API fails
    return [
        'region'     => $name,
        'city'       => $city,
        'alerts'     => [[
            'event'       => "Demo Alert for $city",
            'description' => "Simulated severe weather in $city",
            'severity'    => 'moderate',
            'start'       => time(),
            'end'         => strtotime('+2 hours'),
            'sender_name' => 'Demo Service'
        ]],
        'status'     => 'DEMO',
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
    if (!empty($info['alerts']) && is_array($info['alerts'])) {
        $totalAlerts += count($info['alerts']);
    }
}

$summary = [
    'total_alerts' => $totalAlerts,
    'generated_at' => date('Y-m-d H:i:s')
];

// ✅ Output unified JSON safely with error logging
try {
    echo json_encode(
        [
            'summary' => $summary,
            'regions' => $regions
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    log_event("JSON encode failed: ".$e->getMessage(), "ERROR", ['module'=>'alerts']);
    http_response_code(500);
    echo json_encode([
        'summary' => ['total_alerts' => 0, 'generated_at' => date('Y-m-d H:i:s')],
        'regions' => [],
        'error'   => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

exit;
