<?php
// backend/ethiopia_service/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/rate_limit.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/log.php';

require_user();

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Enforce rate limit
enforce_rate_limit(100);

// CSRF token for forms
$csrfToken = generate_csrf_token();

// Load API settings
$apiConfig = require __DIR__ . '/../../config/api.php';
$apiKey    = $apiConfig['openweathermap'] ?? '';
$rateLimit = $apiConfig['rateLimit'] ?? 100;

// --- Weather fetch (live, no cache for local testing) ---
function getWeatherForCity(string $cityName, string $apiKey): array {
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($cityName) . "&units=metric&appid=" . $apiKey;
    $res = @file_get_contents($url);
    if ($res === false) return ['temp_c' => null, 'condition' => 'Unknown'];

    $data = json_decode($res, true);
    if (!isset($data['main']['temp'])) return ['temp_c' => null, 'condition' => 'Unknown'];

    return [
        'temp_c'    => round($data['main']['temp']),
        'condition' => ucfirst($data['weather'][0]['description'] ?? 'Unknown')
    ];
}

// --- Action routing ---
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

try {
    // --- Add city to favorites ---
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        validate_csrf_token($_POST['csrf_token'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if ($city !== '') {
            $stmt = db()->prepare("SELECT id FROM cities WHERE name = ?");
            $stmt->execute([$city]);
            $cityRow = $stmt->fetch();
            if ($cityRow) {
                $stmt = db()->prepare("INSERT IGNORE INTO favorites (user_id, city_id) VALUES (?, ?)");
                $stmt->execute([$userId, $cityRow['id']]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // --- Delete city from favorites ---
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        validate_csrf_token($_POST['csrf_token'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $stmt = db()->prepare("SELECT id FROM cities WHERE name = ?");
        $stmt->execute([$city]);
        $cityRow = $stmt->fetch();
        if ($cityRow) {
            $stmt = db()->prepare("DELETE FROM favorites WHERE user_id = ? AND city_id = ?");
            $stmt->execute([$userId, $cityRow['id']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // --- List saved cities with live weather ---
    if ($action === 'list') {
        $stmt = db()->prepare("
            SELECT c.name, c.region
            FROM favorites f
            JOIN cities c ON f.city_id = c.id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($cities as $city) {
            $weather = getWeatherForCity($city['name'], $apiKey);
            $result[] = [
                'name'      => $city['name'],
                'temp_c'    => $weather['temp_c'],
                'condition' => $weather['condition']
            ];
        }

        echo json_encode([
            'cities'     => $result,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ], JSON_PRETTY_PRINT);
        exit;
    }
} catch (Exception $e) {
    log_event("Dashboard error: " . $e->getMessage(), "ERROR", ['module'=>'dashboard','user_id'=>$userId]);
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
