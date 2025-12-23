<?php
// backend/ethiopia_service/forecast.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user']['id'] ?? null;

// ✅ Enforce rate limit only for logged-in accounts
if ($userId) {
    enforce_rate_limit($apiConfig['rateLimit'] ?? 100);
}

$city   = trim($_GET['city'] ?? ($_POST['city'] ?? ''));
$cityId = $_GET['city_id'] ?? ($_POST['city_id'] ?? null);

if ($city === '' && !$cityId) {
    http_response_code(400);
    echo json_encode(['error' => 'City parameter required']);
    exit;
}

$openWeatherKey = $apiConfig['openweathermap'] ?? null;
if (!$openWeatherKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Service configuration error']);
    exit;
}

// --- Helper ---
function curl_get(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $res = curl_exec($ch);
    if ($res === false) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return $res;
}

function getForecastForCity(string $cityName, string $apiKey): ?array {
    $geoUrl = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid=" . $apiKey;
    $geoRes = curl_get($geoUrl);
    if (!$geoRes) return null;
    $geoData = json_decode($geoRes, true);
    if (!is_array($geoData) || empty($geoData)) return null;

    $lat = $geoData[0]['lat'] ?? null;
    $lon = $geoData[0]['lon'] ?? null;
    if (!$lat || !$lon) return null;

    $url = "https://api.openweathermap.org/data/2.5/onecall?lat={$lat}&lon={$lon}&exclude=current,minutely,hourly,alerts&units=metric&appid={$apiKey}";
    $res = curl_get($url);
    if (!$res) return null;
    $data = json_decode($res, true);
    if (!isset($data['daily'])) return null;

    $forecast = [];
    foreach ($data['daily'] as $day) {
        $forecast[] = [
            'd'    => date('Y-m-d', $day['dt']),
            'temp' => round($day['temp']['day']),
            'cond' => ucfirst($day['weather'][0]['description'] ?? 'Unknown')
        ];
    }
    return $forecast;
}

// --- Fetch forecast ---
$forecast = $city ? getForecastForCity($city, $openWeatherKey) : null;
$cachedAt = null;

if ($forecast === null) {
    // Failover: try cached data
    $stmt = db()->prepare("SELECT payload, updated_at 
                           FROM weather_cache 
                           WHERE city_id = :city_id AND type = 'forecast'");
    $stmt->execute([':city_id' => $cityId]);
    $cache = $stmt->fetch();

    if ($cache) {
        log_event("API failed, serving cached forecast", "WARN", ['module'=>'forecast','city_id'=>$cityId]);
        $forecast = json_decode($cache['payload'], true);
        $cachedAt = $cache['updated_at'];
        $source   = 'cache';
    } else {
        log_event("API failed, no cache available", "ERROR", ['module'=>'forecast','city_id'=>$cityId]);
        http_response_code(503);
        echo json_encode(['error' => 'Forecast unavailable, please try later']);
        exit;
    }
} else {
    // Update cache
    $stmt = db()->prepare("INSERT INTO weather_cache (city_id, type, payload) 
                           VALUES ((SELECT id FROM cities WHERE name = :city), 'forecast', :payload) 
                           ON DUPLICATE KEY UPDATE 
                           payload = VALUES(payload), 
                           updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([':city' => $city, ':payload' => json_encode($forecast)]);
    log_event("Forecast served", "INFO", ['module'=>'forecast','city'=>$city]);
    $source = 'api';
    $cachedAt = date('Y-m-d H:i:s');
}

// --- Response shaping ---
$response = [
    'city'  => $city ?: $cityId,
    'source'=> $source,
    'data'  => [
        'days'        => $forecast,
        'generated_at'=> $cachedAt
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
