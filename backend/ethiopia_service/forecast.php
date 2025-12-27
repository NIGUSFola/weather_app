<?php
// backend/ethiopia_service/forecast.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/weather_api.php';

$userId = $_SESSION['user']['id'] ?? null;
$role   = $_SESSION['user']['role'] ?? null;

// ✅ Enforce rate limit only for logged-in accounts
if ($userId) {
    enforce_rate_limit($apiConfig['rateLimit'] ?? 100);
}

$city   = trim($_GET['city'] ?? ($_POST['city'] ?? ''));
$cityId = $_GET['city_id'] ?? ($_POST['city_id'] ?? null);

// ✅ If only city_id is provided, fetch city name from DB
if ($city === '' && $cityId) {
    try {
        $stmt = db()->prepare("SELECT name FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $city = $row['name'];
        }
    } catch (Exception $e) {
        log_event("City lookup failed: " . $e->getMessage(), "ERROR", ['module'=>'forecast']);
    }
}

if ($city === '') {
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

// --- Fetch forecast ---
$forecast = getForecastForCity($city, $openWeatherKey);
$cachedAt = null;
$source   = 'api';

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
    $cachedAt = date('Y-m-d H:i:s');
}

// --- Response shaping ---
$response = [
    'city'   => $city,
    'source' => $source,
    'data'   => [
        'days'        => $forecast,
        'generated_at'=> $cachedAt
    ]
];

// ✅ Add user forecast if logged in as user
if ($userId && $role === 'user') {
    $response['user'] = [
        'forecast' => array_map(function($day) {
            return [
                'date'      => $day['date'] ?? '',
                'min_temp'  => $day['min_temp'] ?? $day['temperature'] ?? '',
                'max_temp'  => $day['max_temp'] ?? $day['temperature'] ?? '',
                'condition' => $day['condition'] ?? '',
                'icon'      => $day['icon'] ?? null
            ];
        }, $forecast)
    ];
}

// ✅ Add admin forecast if logged in as admin
if ($userId && $role === 'admin') {
    $response['admin'] = [
        'forecast' => array_map(function($day) {
            return [
                'date'      => $day['date'] ?? '',
                'min_temp'  => $day['min_temp'] ?? $day['temperature'] ?? '',
                'max_temp'  => $day['max_temp'] ?? $day['temperature'] ?? '',
                'condition' => $day['condition'] ?? '',
                'icon'      => $day['icon'] ?? null
            ];
        }, $forecast),
        'meta' => [
            'record_count' => count($forecast),
            'generated_at' => $cachedAt
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
