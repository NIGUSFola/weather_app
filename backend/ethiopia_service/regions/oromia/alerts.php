<?php
// backend/ethiopia_service/regions/oromia/alerts.php
// Oromia alerts service with caching + configurable TTL

require_once __DIR__ . '/../../../helpers/weather_api.php';
require_once __DIR__ . '/../../../helpers/alerts.php';
require_once __DIR__ . '/../../../../config/db.php';

$apiConfig = require __DIR__ . '/../../../../config/api.php';

function oromia_alerts(): array {
    global $apiConfig;
    $apiKey = $apiConfig['openweathermap'] ?? null;
    if (!$apiKey) {
        return ['city' => 'Shashamane', 'alerts' => [], 'data_status' => 'NO_API_KEY'];
    }

    $pdo    = db();
    $region = 'Oromia';
    $type   = 'alerts';

    // 🔧 Get TTL from system_settings (default 2 minutes if not set)
    $ttl = 120; // fallback
    try {
        $stmt = $pdo->query("SELECT cache_duration FROM system_settings LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ttl = max(60, (int)$row['cache_duration'] * 60); // enforce minimum 1 minute
        }
    } catch (Exception $e) {
        // ignore errors, fallback to default TTL
    }

    // 1. Check cache
    $stmt = $pdo->prepare("SELECT payload, updated_at FROM weather_cache WHERE region=? AND type=?");
    $stmt->execute([$region, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $age = time() - strtotime($row['updated_at']);
        if ($age < $ttl) {
            return [
                'city'        => 'Shashamane',
                'alerts'      => json_decode($row['payload'], true) ?? [],
                'data_status' => 'CACHED'
            ];
        }
    }

    // 2. Fetch from API
    try {
        $alerts = getAlertsForCity('Shashamane', $apiKey, 'en');
        if (is_array($alerts) && count($alerts) > 0) {
            $stmt = $pdo->prepare("REPLACE INTO weather_cache(region,type,payload,updated_at) VALUES(?,?,?,NOW())");
            $stmt->execute([$region, $type, json_encode($alerts)]);
            return ['city' => 'Shashamane', 'alerts' => $alerts, 'data_status' => 'FRESH'];
        }
        // ✅ Even if no alerts, service is healthy
        return ['city' => 'Shashamane', 'alerts' => [], 'data_status' => 'NO_ALERTS'];
    } catch (Exception $e) {
        if ($row) {
            return [
                'city'        => 'Shashamane',
                'alerts'      => json_decode($row['payload'], true) ?? [],
                'data_status' => 'STALE'
            ];
        }
        return [
            'city'        => 'Shashamane',
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
        $result = oromia_alerts();
        $response = [
            'region'      => 'Oromia',
            'city'        => $result['city'],
            'alerts'      => normalize_alerts($result['alerts'] ?? []),
            'status'      => 'OK',                  // always OK if service responds
            'data_status' => $result['data_status'] ?? 'UNKNOWN',
            'checked_at'  => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'region'      => 'Oromia',
            'city'        => 'Shashamane',
            'alerts'      => [],
            'status'      => 'FAIL',
            'data_status' => 'FAIL',
            'error'       => $e->getMessage(),
            'checked_at'  => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
