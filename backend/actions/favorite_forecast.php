<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../helpers/weather_api.php';
require_once __DIR__ . '/../helpers/forecast.php'; // ✅ normalization helper

require_user();

$userId    = $_SESSION['user']['id'] ?? null;
$apiConfig = require __DIR__ . '/../../config/api.php';
$apiKey    = $apiConfig['openweathermap'] ?? '';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['status'=>'FAIL','message'=>'Missing API key']);
    exit;
}

// ✅ Map city names → region keys
$cityToRegion = [
    'Addis Ababa' => 'addis_ababa',
    'Shashamane'  => 'oromia',
    'Hawassa'     => 'south',
    'Bahir Dar'   => 'amhara'
];

try {
    $stmt = db()->prepare("SELECT c.name FROM favorites f JOIN cities c ON f.city_id=c.id WHERE f.user_id=? ORDER BY f.created_at ASC");
    $stmt->execute([$userId]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $favorites = [];
    foreach ($cities as $city) {
        $cityName  = $city['name'];
        $regionKey = $cityToRegion[$cityName] ?? $cityName; // ✅ resolve to region

        // Cache check
        $stmtCache = db()->prepare("SELECT payload, updated_at FROM weather_cache WHERE region=? AND type='forecast'");
        $stmtCache->execute([$regionKey]);
        $row = $stmtCache->fetch(PDO::FETCH_ASSOC);

        $forecast = null;
        $status   = 'NO_FORECAST';

        if ($row && (time() - strtotime($row['updated_at']) < 600)) {
            $forecast = json_decode($row['payload'], true);
            $status   = 'CACHED';
        }

        if (!$forecast) {
            try {
                $forecast = getForecastForCity($cityName, $apiKey, 'en', 'metric');
                if ($forecast && count($forecast) > 0) {
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache(region,type,payload,updated_at) VALUES(?,?,?,NOW())");
                    $stmtCache->execute([$regionKey, 'forecast', json_encode($forecast)]);
                    $status = 'OK';
                } else {
                    $status = 'NO_FORECAST';
                }
            } catch (Exception $e) {
                $status = $row ? 'STALE' : 'FAIL';
                $forecast = $row ? json_decode($row['payload'], true) : [];
            }
        }

        $favorites[] = [
            'city'     => $cityName,
            'forecast' => normalize_forecast($forecast ?? []),
            'status'   => $status
        ];
    }

    log_event("Favorite forecast fetched", "INFO", ['module'=>'favorite_forecast','user_id'=>$userId]);

    echo json_encode([
        'status'    => 'OK',
        'favorites' => $favorites,
        'checked_at'=> date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    log_event("Favorite forecast failed: ".$e->getMessage(), "ERROR", ['module'=>'favorite_forecast','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['status'=>'FAIL','message'=>'Server error']);
}
