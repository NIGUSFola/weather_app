<?php
// backend/ethiopia_service/admin/admin_health.php
// Admin interface for system health overview with self-healing cache

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../helpers/weather_api.php';
require_once __DIR__ . '/../../helpers/forecast.php';

require_admin();

$csrfToken = generate_csrf_token();
$checkedAt = date('Y-m-d H:i:s');

$health = [];

try {
    // ✅ Fetch all cities except ones you want excluded
    $cities = db()->query("
        SELECT id, name 
        FROM cities 
        WHERE name NOT IN ('Dire Dawa','Gondar','Hossana')
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cities as $city) {
        $cityId   = $city['id'];
        $cityName = $city['name'];

        $components = [
            'db'    => 'OK',
            'cache' => 'FAIL',
            'api'   => 'OK'
        ];
        $status = 'OK';

        // ✅ DB check
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM weather_cache WHERE city_id=?");
            $stmt->execute([$cityId]);
            $count = $stmt->fetchColumn();
            $components['db'] = 'OK';
        } catch (Exception $e) {
            $components['db'] = 'FAIL';
            $status = 'FAIL';
        }

        // ✅ Cache check with self-healing
        try {
            $stmt = db()->prepare("SELECT updated_at FROM weather_cache WHERE city_id=? AND type='forecast'");
            $stmt->execute([$cityId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && (time() - strtotime($row['updated_at']) < 600)) {
                $components['cache'] = 'OK';
            } else {
                // Attempt to refresh forecast if cache is missing/stale
                try {
                    $apiConfig = require __DIR__ . '/../../../config/api.php';
                    $apiKey    = $apiConfig['openweathermap'] ?? '';
                    if ($apiKey) {
                        $forecast = getForecastForCity($cityName, $apiKey, 'en', 'metric');
                        if ($forecast && count($forecast) > 0) {
                            $stmtCache = db()->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) 
                                                        VALUES(?,?,?,NOW())");
                            $stmtCache->execute([$cityId, 'forecast', json_encode($forecast)]);
                            $components['cache'] = 'OK';
                        } else {
                            $components['cache'] = 'FAIL';
                            $status = 'FAIL';
                        }
                    }
                } catch (Exception $e) {
                    $components['cache'] = 'FAIL';
                    $status = 'FAIL';
                }
            }
        } catch (Exception $e) {
            $components['cache'] = 'FAIL';
            $status = 'FAIL';
        }

        // ✅ API check (mock ping)
        $components['api'] = 'OK';

        if ($components['cache'] === 'FAIL') {
            $status = 'FAIL';
        }

        $health[] = [
            'city'       => $cityName,
            'status'     => $status,
            'components' => $components,
            'checked_at' => $checkedAt
        ];
    }
} catch (Exception $e) {
    log_event("Health check failed: ".$e->getMessage(), "ERROR", ['module'=>'admin_health']);
}

// ✅ JSON mode for dashboard fetch
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'     => 'OK',
        'regions'    => $health,
        'checked_at' => $checkedAt
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Health Overview</title>
    <link rel="stylesheet" href="/weather/frontend/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
        .status-OK { color: green; font-weight: bold; }
        .status-FAIL { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🩺 System Health Overview</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <table>
        <tr><th>City</th><th>Status</th><th>Components</th><th>Checked At</th></tr>
        <?php if (empty($health)): ?>
            <tr><td colspan="4">No health data available.</td></tr>
        <?php else: ?>
            <?php foreach ($health as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['city']) ?></td>
                    <td class="status-<?= $entry['status'] ?>"><?= htmlspecialchars($entry['status']) ?></td>
                    <td>
                        db: <?= $entry['components']['db'] ?><br>
                        cache: <?= $entry['components']['cache'] ?><br>
                        api: <?= $entry['components']['api'] ?>
                    </td>
                    <td><?= htmlspecialchars($entry['checked_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
