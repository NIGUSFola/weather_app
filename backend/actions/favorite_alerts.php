<?php
// backend/actions/favorite_alerts.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../helpers/weather_api.php';
require_once __DIR__ . '/../helpers/alerts.php'; // ✅ normalization helper

require_user();

$userId    = $_SESSION['user']['id'] ?? null;
$apiConfig = require __DIR__ . '/../../config/api.php';
$apiKey    = $apiConfig['openweathermap'] ?? '';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'FAIL',
        'message' => 'Missing API key'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ✅ Get user favorites with city_id
    $stmt = db()->prepare("SELECT c.id AS city_id, c.name 
                           FROM favorites f 
                           JOIN cities c ON f.city_id=c.id 
                           WHERE f.user_id=? 
                           ORDER BY f.created_at ASC");
    $stmt->execute([$userId]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $favorites = [];
    foreach ($cities as $city) {
        $cityId   = $city['city_id'];
        $cityName = $city['name'];

        // ✅ Cache check by city_id
        $stmtCache = db()->prepare("SELECT payload, updated_at 
                                    FROM weather_cache 
                                    WHERE city_id=? AND type='alerts'");
        $stmtCache->execute([$cityId]);
        $row = $stmtCache->fetch(PDO::FETCH_ASSOC);

        $alerts = null;
        $status = 'NO_ALERTS';

        if ($row && (time() - strtotime($row['updated_at']) < 120)) {
            $alerts = json_decode($row['payload'], true);
            $status = 'CACHED';
        }

        if (!$alerts || count($alerts) === 0) {
            try {
                $alerts = getAlertsForCity($cityName, $apiKey, 'en');
                if ($alerts && count($alerts) > 0) {
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) 
                                                VALUES(?,?,?,NOW())");
                    $stmtCache->execute([$cityId, 'alerts', json_encode($alerts)]);
                    $status = 'OK';
                } else {
                    // ✅ Demo fallback
                    $demoAlert = [[
                        'event'       => "Demo Alert for $cityName",
                        'description' => "Testing fallback: simulated severe weather in $cityName",
                        'severity'    => 'moderate',
                        'start'       => time(),
                        'end'         => strtotime('+3 hours'),
                        'sender_name' => 'Demo Service'
                    ]];
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) 
                                                VALUES(?,?,?,NOW())");
                    $stmtCache->execute([$cityId, 'alerts', json_encode($demoAlert)]);
                    $alerts = $demoAlert;
                    $status = 'DEMO';
                }
            } catch (Exception $e) {
                // API fails → demo fallback
                $demoAlert = [[
                    'event'       => "Demo Alert for $cityName",
                    'description' => "Testing fallback: simulated severe weather in $cityName",
                    'severity'    => 'moderate',
                    'start'       => time(),
                    'end'         => strtotime('+3 hours'),
                    'sender_name' => 'Demo Service'
                ]];
                $alerts = $demoAlert;
                $status = 'DEMO_FAIL';
            }
        }

        $favorites[] = [
            'city'   => $cityName,
            'alerts' => normalize_alerts($alerts ?? []),
            'status' => $status
        ];
    }

    log_event("Favorite alerts fetched", "INFO", ['module'=>'favorite_alerts','user_id'=>$userId]);

    echo json_encode([
        'status'    => 'OK',
        'favorites' => $favorites,
        'checked_at'=> date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    log_event("Favorite alerts failed: ".$e->getMessage(), "ERROR", ['module'=>'favorite_alerts','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode([
        'status'  => 'FAIL',
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
