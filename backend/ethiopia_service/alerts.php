<?php
// weather_app/backend/ethiopia_service/alerts.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../backend/helpers/log.php';
require_once __DIR__ . '/../../backend/helpers/alerts.php'; // must define getAlertsForCity(int $cityId): array

$response = [
    'status'     => 'OK',
    'checked_at' => date('Y-m-d H:i:s'),
];

try {
    // ✅ Admin checker: ?city_id=123
    $cityId = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;

    if ($cityId > 0) {
        $alerts = getAlertsForCity($cityId) ?? [];
        $response['public'] = [
            'alerts' => is_array($alerts) ? $alerts : []
        ];
    } else {
        // ✅ Default national/public preview: Addis Ababa
        $stmt = db()->prepare("SELECT id FROM cities WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => 'Addis Ababa']);
        $defaultCityId = (int)($stmt->fetchColumn() ?: 0);

        $alerts = $defaultCityId ? (getAlertsForCity($defaultCityId) ?? []) : [];
        $response['public'] = [
            'alerts' => is_array($alerts) ? $alerts : [],
            'city'   => 'Addis Ababa'
        ];
    }

    // ✅ User favorites alerts
    if (!empty($_SESSION['user']['id']) && $_SESSION['user']['role'] === 'user') {
        $userId = (int)$_SESSION['user']['id'];
        $favoritesStmt = db()->prepare("
            SELECT c.id, c.name 
            FROM favorite_cities f 
            JOIN cities c ON c.id = f.city_id 
            WHERE f.user_id = :uid
        ");
        $favoritesStmt->execute([':uid' => $userId]);
        $favorites = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);

        $userAlerts = [];
        foreach ($favorites as $fav) {
            $cityAlerts = getAlertsForCity((int)$fav['id']) ?? [];
            $userAlerts[$fav['name']] = [
                'alerts'    => is_array($cityAlerts) ? $cityAlerts : [],
                'cached_at' => date('Y-m-d H:i:s')
            ];
        }
        if (!empty($userAlerts)) {
            $response['user'] = $userAlerts;
        }
    }

    // ✅ Admin metadata
    if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        $response['admin'] = [
            'meta' => [
                'generated_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
} catch (Throwable $e) {
    $response['status'] = 'FAIL';
    $response['error']  = $e->getMessage();
    if (!isset($response['public'])) {
        $response['public'] = ['alerts' => []];
    }
    log_event("Alerts endpoint failed: " . $e->getMessage(), "ERROR", ['module' => 'alerts']);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
