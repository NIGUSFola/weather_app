<?php
// weather_app/cron/refresh_cache.php
// Cron job to refresh weather_cache with distributed lock protection
// Guarantees type='forecast' for all inserts

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../config/db.php';              // DB connection
require_once __DIR__ . '/../backend/helpers/log.php';    // Logging helper
require_once __DIR__ . '/../config/api.php';             // API key config

$pdo = db();
$lockName = 'cache_refresh';

// --- Acquire lock ---
try {
    $stmt = $pdo->prepare("
        INSERT INTO distributed_locks (name, acquired_at)
        VALUES (:name, NOW())
        ON DUPLICATE KEY UPDATE acquired_at = VALUES(acquired_at)
    ");
    $stmt->execute([':name' => $lockName]);
    log_event("Lock acquired for cache refresh", "INFO");
} catch (Exception $e) {
    log_event("Failed to acquire lock: " . $e->getMessage(), "ERROR");
    exit; // another process holds the lock
}

// --- Refresh cache for each city ---
$cities = $pdo->query("SELECT id, name FROM cities")->fetchAll();
$apiKey = $apiConfig['openweathermap'] ?? null;

foreach ($cities as $city) {
    $cityId   = $city['id'];
    $cityName = $city['name'];

    $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($cityName) . "&appid=" . $apiKey;
    $res = @file_get_contents($url);

    if ($res !== false) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO weather_cache (city_id, type, payload, updated_at)
                VALUES (:city_id, 'forecast', :payload, NOW())
                ON DUPLICATE KEY UPDATE 
                    payload = VALUES(payload),
                    updated_at = VALUES(updated_at)
            ");
            $stmt->execute([
                ':city_id' => $cityId,
                ':payload' => $res
            ]);

            log_event("Cache refreshed successfully for $cityName", "INFO");
        } catch (Exception $e) {
            log_event("DB insert failed for $cityName: " . $e->getMessage(), "ERROR");
        }
    } else {
        log_event("API fetch failed for $cityName (network or API issue)", "WARN");
    }
}

// --- Release lock ---
try {
    $pdo->prepare("DELETE FROM distributed_locks WHERE name = :name")
        ->execute([':name' => $lockName]);
    log_event("Lock released after cache refresh", "INFO");
} catch (Exception $e) {
    log_event("Failed to release lock: " . $e->getMessage(), "ERROR");
}

echo "✅ Cache refresh complete\n";
