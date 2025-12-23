<?php
// backend/actions/favorite_forecast.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId    = $_SESSION['user']['id'] ?? null;
$apiConfig = require __DIR__ . '/../../config/api.php';
$apiKey    = $apiConfig['openweathermap'] ?? '';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error'=>'Service configuration error']);
    exit;
}

try {
    // Get all favorite cities for this user
    $stmt = db()->prepare("
        SELECT c.name
        FROM favorites f
        JOIN cities c ON f.city_id = c.id
        WHERE f.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($cities as $city) {
        $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($city['name']) . "&units=metric&appid=" . $apiKey;
        $res = @file_get_contents($url);
        $data = $res ? json_decode($res, true) : null;

        $result[] = [
            'city'     => $city['name'],
            'forecast' => $data ? array_slice($data['list'], 0, 3) : null // next 3 forecast periods
        ];
    }

    log_event("Favorite forecast fetched", "INFO", ['module'=>'favorite_forecast','user_id'=>$userId]);
    echo json_encode(['favorites'=>$result], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    log_event("Favorite forecast failed: ".$e->getMessage(), "ERROR", ['module'=>'favorite_forecast','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
}
