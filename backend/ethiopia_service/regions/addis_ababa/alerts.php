<?php
// backend/ethiopia_service/regions/addis_ababa/alerts.php
// Addis Ababa alerts service with caching + consistent health reporting

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../helpers/alerts.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function addis_ababa_alerts(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Addis Ababa', 'alerts' => [], 'data_status' => 'NO_API_KEY'];
    }

    $pdo    = db();
    $region = 'Addis Ababa';
    $type   = 'alerts';

    // 1. Check cache (TTL = 2 minutes)
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE region=? AND type=?");
    $stmt->execute([$region, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (time() - strtotime($row['updated_at']) < 120)) {
        return [
            'city'        => 'Addis Ababa',
            'alerts'      => json_decode($row['payload'], true) ?? [],
            'data_status' => 'CACHED'
        ];
    }

    // 2. Fetch from API
    try {
        $alerts = getAlertsForCity('Addis Ababa', $apiKey, 'en');
        if (is_array($alerts) && count($alerts) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache(region,type,payload,updated_at) VALUES(?,?,?,NOW())");
            $stmt->execute([$region, $type, json_encode($alerts)]);
            return ['city' => 'Addis Ababa', 'alerts' => $alerts, 'data_status' => 'FRESH'];
        }
        // ✅ Even if no alerts, service is healthy
        return ['city' => 'Addis Ababa', 'alerts' => [], 'data_status' => 'NO_ALERTS'];
    } catch (Exception $e) {
        if ($row) {
            return [
                'city'        => 'Addis Ababa',
                'alerts'      => json_decode($row['payload'], true) ?? [],
                'data_status' => 'STALE'
            ];
        }
        return [
            'city'        => 'Addis Ababa',
            'alerts'      => [],
            'data_status' => 'FAIL',
            'error'       => $e->getMessage()
        ];
    }
}

// ✅ Only echo JSON if run directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = addis_ababa_alerts();
        $response = [
            'region'      => 'Addis Ababa',
            'city'        => $result['city'],
            'alerts'      => normalize_alerts($result['alerts'] ?? []),
            'status'      => 'OK',                  // always OK if service responds
            'data_status' => $result['data_status'] ?? 'UNKNOWN',
            'checked_at'  => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'region'      => 'Addis Ababa',
            'city'        => 'Addis Ababa',
            'alerts'      => [],
            'status'      => 'FAIL',
            'data_status' => 'FAIL',
            'error'       => $e->getMessage(),
            'checked_at'  => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
