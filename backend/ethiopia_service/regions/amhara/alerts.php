<?php
// backend/ethiopia_service/regions/amhara/alerts.php
// Amhara alerts service (Bahir Dar) with caching + demo fallback

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../helpers/alerts.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function amhara_alerts(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Bahir Dar', 'alerts' => [], 'data_status' => 'NO_API_KEY'];
    }

    $pdo   = db();
    $city  = 'Bahir Dar';
    $type  = 'alerts';
    $ttl   = 120; // 2 minutes

    // 🔎 Resolve city_id from cities table
    $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
    $stmt->execute([$city]);
    $cityRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $cityId  = $cityRow['id'] ?? null;

    if (!$cityId) {
        return ['city' => $city, 'alerts' => [], 'data_status' => 'NO_CITY_ID'];
    }

    // 1. Check cache
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE city_id=? AND type=?");
    $stmt->execute([$cityId, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (time() - strtotime($row['updated_at']) < $ttl)) {
        return [
            'city'        => $city,
            'alerts'      => json_decode($row['payload'], true) ?? [],
            'data_status' => 'CACHED'
        ];
    }

    // 2. Try to fetch live alerts
    try {
        $alerts = getAlertsForCity($city, $apiKey);
        if (is_array($alerts) && count($alerts) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) VALUES(?,?,?,NOW())");
            $stmt->execute([$cityId, $type, json_encode($alerts)]);
            return ['city' => $city, 'alerts' => $alerts, 'data_status' => 'FRESH'];
        }

        // ✅ Permanent demo fallback if no alerts
        $demoAlert = [[
            'event'       => 'Demo High Wind Warning',
            'description' => 'Strong winds expected in Bahir Dar region',
            'severity'    => 'severe',
            'start'       => time(),
            'end'         => strtotime('+3 hours'),
            'sender_name' => 'Demo Service'
        ]];
        $stmt = $pdo->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) VALUES(?,?,?,NOW())");
        $stmt->execute([$cityId, $type, json_encode($demoAlert)]);
        return ['city' => $city, 'alerts' => $demoAlert, 'data_status' => 'DEMO'];
    } catch (Exception $e) {
        // If API fails, still return demo fallback
        $demoAlert = [[
            'event'       => 'Demo High Wind Warning',
            'description' => 'Strong winds expected in Bahir Dar region',
            'severity'    => 'severe',
            'start'       => time(),
            'end'         => strtotime('+3 hours'),
            'sender_name' => 'Demo Service'
        ]];
        return ['city' => $city, 'alerts' => $demoAlert, 'data_status' => 'DEMO_FAIL', 'error' => $e->getMessage()];
    }
}

// ✅ Only echo JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = amhara_alerts();
        $response = [
            'region'      => 'Amhara',
            'city'        => $result['city'],
            'alerts'      => normalize_alerts($result['alerts'] ?? []),
            'status'      => 'OK',
            'data_status' => $result['data_status'] ?? 'UNKNOWN',
            'checked_at'  => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'region'      => 'Amhara',
            'city'        => 'Bahir Dar',
            'alerts'      => [],
            'status'      => 'FAIL',
            'data_status' => 'FAIL',
            'error'       => $e->getMessage(),
            'checked_at'  => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
