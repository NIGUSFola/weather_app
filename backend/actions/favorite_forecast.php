<?php
// backend/actions/favorite_forecast.php
// Returns forecasts for user's favorite cities with caching and normalization

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../helpers/weather_api.php';
require_once __DIR__ . '/../helpers/forecast.php';
require_once __DIR__ . '/../helpers/config.php';
require_once __DIR__ . '/../helpers/rate_limit.php';

require_user();

$userId        = $_SESSION['user']['id'] ?? null;
$apiKey        = getApiKey();
$cacheDuration = getCacheDuration();
$rateLimit     = getRateLimit();

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['status'=>'FAIL','message'=>'Missing API key'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = db()->prepare("
        SELECT c.id AS city_id, c.name 
        FROM favorites f 
        JOIN cities c ON f.city_id = c.id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at ASC
    ");
    $stmt->execute([$userId]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $favorites = [];
    foreach ($cities as $city) {
        $cityId   = $city['city_id'];
        $cityName = $city['name'];

        $stmtCache = db()->prepare("SELECT payload, updated_at FROM weather_cache WHERE city_id=? AND type='forecast'");
        $stmtCache->execute([$cityId]);
        $row = $stmtCache->fetch(PDO::FETCH_ASSOC);

        $forecast = [];
        $status   = 'NO_FORECAST';

        if ($row) {
            $age = time() - strtotime($row['updated_at']);
            if ($age < $cacheDuration * 60) {
                $forecast = json_decode($row['payload'], true) ?? [];
                $status   = 'CACHED';
            } else {
                $status   = 'STALE';
                $forecast = json_decode($row['payload'], true) ?? [];
            }
        }

        if (empty($forecast)) {
            try {
                enforce_rate_limit($rateLimit);
                $rawForecast = getForecastForCity($cityName, $apiKey, 'en', 'metric');
                $normalized  = normalize_forecast($rawForecast ?? []);
                if (!empty($normalized)) {
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache (city_id,type,payload,updated_at) VALUES (?,?,?,NOW())");
                    $stmtCache->execute([$cityId,'forecast',json_encode($normalized)]);
                    $forecast = $normalized;
                    $status   = 'OK';
                } else {
                    $status = 'NO_FORECAST';
                }
            } catch (Exception $e) {
                $status   = $row ? 'STALE' : 'FAIL';
                $forecast = $row ? (json_decode($row['payload'], true) ?? []) : [];
                log_event("Forecast API failed for $cityName: ".$e->getMessage(), "ERROR", [
                    'module'=>'favorite_forecast','user_id'=>$userId,'city_id'=>$cityId
                ]);
            }
        }

        $favorites[] = ['city'=>$cityName,'forecast'=>$forecast,'status'=>$status];
    }

    log_event("Favorite forecast fetched successfully", "INFO", [
        'module'=>'favorite_forecast','user_id'=>$userId,'cities_checked'=>array_column($cities,'name')
    ]);

    echo json_encode([
        'status'=>'OK',
        'favorites'=>$favorites,
        'checked_at'=>date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    log_event("Favorite forecast failed: ".$e->getMessage(), "ERROR", ['module'=>'favorite_forecast','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['status'=>'FAIL','message'=>'Server error','error'=>$e->getMessage()], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}
