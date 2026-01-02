<?php
// backend/ethiopia_service/regions/addis_ababa/health.php

require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../../../helpers/log.php';

function addis_ababa_health(): array {
    $regionName = 'Addis Ababa';
    $status     = 'OK';
    $components = [
        'db'    => 'OK',
        'cache' => 'OK',
        'api'   => 'OK'
    ];

    // ✅ DB connectivity check
    try {
        db()->query("SELECT 1");
    } catch (Exception $e) {
        $status = 'FAIL';
        $components['db'] = 'FAIL';
        log_event("Health check failed for $regionName (DB): " . $e->getMessage(), "ERROR");
    }

    // ✅ Cache table check (by city_id for Addis Ababa’s main city)
    try {
        $pdo = db();
        // Adjust city name to the one you use in your Addis Ababa forecast service
        $stmt = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
        $stmt->execute(['Addis Ababa']); // Example city for Addis Ababa region
        $cityRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $cityId  = $cityRow['id'] ?? null;

        if ($cityId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM weather_cache WHERE city_id = ? AND type = 'forecast'");
            $stmt->execute([$cityId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false || (int)$row['cnt'] === 0) {
                $components['cache'] = 'EMPTY';
                $status = 'FAIL';
            } else {
                $components['cache'] = "OK ({$row['cnt']} rows)";
            }
        } else {
            $components['cache'] = 'NO_CITY_ID';
            $status = 'FAIL';
        }
    } catch (Exception $e) {
        $components['cache'] = 'FAIL';
        $status = 'FAIL';
        log_event("Health check failed for $regionName (Cache): " . $e->getMessage(), "ERROR");
    }

    // ✅ API key presence check
    $apiConfig = require __DIR__ . '/../../../../config/api.php';
    if (empty($apiConfig['openweathermap'])) {
        $components['api'] = 'MISSING';
        $status = 'FAIL';
    }

    return [
        'region'     => $regionName,
        'status'     => $status,
        'components' => $components,
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

// ✅ Standalone mode
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(addis_ababa_health(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
