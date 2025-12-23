<?php
// backend/ethiopia_service/current.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/log.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../config.php';   // local config for service

$userId = $_SESSION['user']['id'] ?? null;
$role   = $_SESSION['user']['role'] ?? null;

// Enforce rate limit for logged-in users
if ($userId) {
    enforce_rate_limit($apiConfig['rateLimit'] ?? 100);
}

$city = isset($_GET['city']) ? trim($_GET['city']) : 'Addis Ababa';

// Mock data (replace with API integration later)
$mockData = [
    'Addis Ababa' => ['temp_c' => 20, 'condition' => 'Partly Cloudy'],
    'Hossana'     => ['temp_c' => 22, 'condition' => 'Rain Showers'],
    'Hawassa'     => ['temp_c' => 24, 'condition' => 'Sunny'],
    'Dire Dawa'   => ['temp_c' => 28, 'condition' => 'Hot & Dry'],
];

$data = $mockData[$city] ?? ['temp_c' => null, 'condition' => 'Unknown'];

// Log to DB weather_history
try {
    $stmt = db()->prepare("SELECT id FROM cities WHERE name = ?");
    $stmt->execute([$city]);
    $cityRow = $stmt->fetch();

    if ($cityRow) {
        $stmt = db()->prepare("INSERT INTO weather_history (city_id, temp_c, weather_condition) VALUES (?, ?, ?)");
        $stmt->execute([$cityRow['id'], $data['temp_c'], $data['condition']]);
        log_event("Weather history logged for $city", "INFO", ['module'=>'current','city'=>$city]);
    }
} catch (Exception $e) {
    log_event("Failed to log weather history: " . $e->getMessage(), "ERROR", ['module'=>'current','city'=>$city]);
}

// --- Response shaping ---
$response = [
    'city'      => $city,
    'temp_c'    => $data['temp_c'],
    'condition' => $data['condition'],
    'timestamp' => date('c'),
    'public'    => [
        'status' => 'demo',
        'temp_c' => $data['temp_c'],
        'condition' => $data['condition']
    ]
];

if ($userId && $role === 'user') {
    $response['user'] = [
        'status' => 'ok',
        'temp_c' => $data['temp_c'],
        'condition' => $data['condition']
    ];
}

if ($userId && $role === 'admin') {
    $response['admin'] = [
        'status' => 'admin',
        'meta'   => [
            'logged' => isset($cityRow) ? true : false,
            'generated_at' => date('Y-m-d H:i:s')
        ],
        'raw'    => $data
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
