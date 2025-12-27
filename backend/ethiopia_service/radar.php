<?php
// backend/ethiopia_service/radar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../../config/db.php';

// ✅ Correct config path
$apiConfig = require __DIR__ . '/../../config/api.php';

$userId = $_SESSION['user']['id'] ?? null;
$role   = $_SESSION['user']['role'] ?? null;

// Enforce rate limit only for logged-in accounts
if ($userId) {
    enforce_rate_limit($apiConfig['rateLimit'] ?? 100);
}

$city   = $_GET['city'] ?? ($_POST['city'] ?? 'Addis Ababa'); // default for public
$apiKey = $apiConfig['openweathermap'] ?? null;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['status'=>'FAIL','message'=>'Missing API key']);
    exit;
}

// --- Helper: convert lat/lon to tile coordinates ---
function latLonToTileXY($lat, $lon, $zoom) {
    $x = floor((($lon + 180) / 360) * pow(2, $zoom));
    $y = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * pow(2, $zoom));
    return [$x, $y];
}

// --- Radar fetch ---
function getRadarForCity($cityName, $apiKey) {
    $geoUrl = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid=" . $apiKey;
    $geoRes = @file_get_contents($geoUrl);
    if ($geoRes === false) return null;

    $geoData = json_decode($geoRes, true);
    if (empty($geoData)) return null;

    $lat = $geoData[0]['lat'];
    $lon = $geoData[0]['lon'];

    $zoom = 5;
    [$x, $y] = latLonToTileXY($lat, $lon, $zoom);
    $tileUrl = "https://tile.openweathermap.org/map/precipitation_new/$zoom/$x/$y.png?appid=$apiKey";

    return [
        'lat'      => $lat,
        'lon'      => $lon,
        'zoom'     => $zoom,
        'tile_url' => $tileUrl
    ];
}

// --- Fetch radar ---
$radar    = getRadarForCity($city, $apiKey);
$cachedAt = null;

if ($radar === null) {
    // Failover: try cached data
    try {
        $stmt = db()->prepare("SELECT payload, updated_at 
                               FROM weather_cache 
                               WHERE city_id = (SELECT id FROM cities WHERE name = ?)");
        $stmt->execute([$city]);
        $cache = $stmt->fetch();

        if ($cache) {
            log_event("API failed, serving cached radar for $city", "WARN", ['module'=>'radar','city'=>$city]);
            $radar    = json_decode($cache['payload'], true);
            $cachedAt = $cache['updated_at'];
        } else {
            log_event("API failed, no cache available for radar in $city", "ERROR", ['module'=>'radar','city'=>$city]);
            http_response_code(503);
            echo json_encode(['status'=>'FAIL','message'=>'Radar unavailable']);
            exit;
        }
    } catch (Exception $e) {
        log_event("Radar cache lookup failed: " . $e->getMessage(), "ERROR", ['module'=>'radar','city'=>$city]);
        http_response_code(500);
        echo json_encode(['status'=>'FAIL','message'=>'Server error']);
        exit;
    }
} else {
    // Update cache
    try {
        $stmt = db()->prepare("INSERT INTO weather_cache (city_id, payload) 
                               VALUES ((SELECT id FROM cities WHERE name = ?), ?) 
                               ON DUPLICATE KEY UPDATE 
                               payload = VALUES(payload), 
                               updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$city, json_encode($radar)]);
        log_event("Radar served for $city", "INFO", ['module'=>'radar','city'=>$city]);
    } catch (Exception $e) {
        log_event("Failed to update radar cache: " . $e->getMessage(), "ERROR", ['module'=>'radar','city'=>$city]);
    }
}

// --- Response shaping ---
$response = [
    'status'    => 'OK',
    'city'      => $city,
    'cached_at' => $cachedAt,
    'public'    => [
        'status' => 'demo',
        'radar'  => $radar
    ]
];

if ($userId && $role === 'user') {
    $response['user'] = [
        'status' => 'OK',
        'radar'  => $radar
    ];
}

if ($userId && $role === 'admin') {
    $response['admin'] = [
        'status' => 'OK',
        'radar'  => $radar,
        'meta'   => [
            'generated_at' => date('Y-m-d H:i:s'),
            'tile_source'  => 'OpenWeatherMap precipitation_new',
            'record_size'  => strlen(json_encode($radar))
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit; // ✅ ensure no stray output
