<?php
// backend/tests/cache_health_cli.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Global Cache Health Check ===\n";

try {
    // Fetch all cities
    $stmt = db()->query("SELECT id, name FROM cities ORDER BY name ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cities as $city) {
        $cityId   = $city['id'];
        $cityName = $city['name'];

        echo "\nCity: $cityName\n";

        // Forecast cache
        $stmt = db()->prepare("SELECT updated_at, JSON_LENGTH(payload) AS items 
                               FROM weather_cache 
                               WHERE city_id = ? AND type = 'forecast'");
        $stmt->execute([$cityId]);
        $forecast = $stmt->fetch();
        if ($forecast) {
            echo "  Forecast cache ✅ updated_at: {$forecast['updated_at']} | items: {$forecast['items']}\n";
        } else {
            echo "  Forecast cache ❌ missing\n";
        }

        // Alerts cache
        $stmt = db()->prepare("SELECT updated_at, JSON_LENGTH(payload) AS items 
                               FROM weather_cache 
                               WHERE city_id = ? AND type = 'alerts'");
        $stmt->execute([$cityId]);
        $alerts = $stmt->fetch();
        if ($alerts) {
            echo "  Alerts cache ✅ updated_at: {$alerts['updated_at']} | items: {$alerts['items']}\n";
        } else {
            echo "  Alerts cache ❌ missing\n";
        }
    }
} catch (Exception $e) {
    log_event("Cache health CLI failed: ".$e->getMessage(), "ERROR", ['module'=>'cache_health_cli']);
    echo "\n[ERROR] Cache health check failed: ".$e->getMessage()."\n";
}
