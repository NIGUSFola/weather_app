<?php
// backend/ethiopia_service/cache.php
// File-based cache helper

/**
 * Store data in file-based cache.
 *
 * @param string $key   Unique cache key
 * @param mixed  $data  Data to store (will be JSON encoded)
 * @param int    $ttl   Time-to-live in seconds (default 300)
 */
function cache_set(string $key, $data, int $ttl = 300): void {
    $cacheDir  = __DIR__ . "/../../cache";
    $cacheFile = $cacheDir . "/" . hash('sha256', $key) . ".json";

    // Ensure cache directory exists
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true)) {
        error_log("Cache dir create failed: {$cacheDir}");
        return;
    }

    $payload = [
        'expires' => time() + $ttl,
        'data'    => $data
    ];

    if (file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        error_log("Cache write failed: {$cacheFile}");
    }
}

/**
 * Retrieve data from file-based cache.
 *
 * @param string $key        Unique cache key
 * @param bool   $allowStale If true, return stale data instead of deleting
 * @return mixed|null        Cached data or null if not found/expired
 */
function cache_get(string $key, bool $allowStale = false) {
    $cacheFile = __DIR__ . "/../../cache/" . hash('sha256', $key) . ".json";

    if (!file_exists($cacheFile)) {
        return null;
    }

    $raw = file_get_contents($cacheFile);
    $payload = json_decode($raw, true);

    // If corrupted or missing fields, remove file
    if (!is_array($payload) || !isset($payload['expires']) || !array_key_exists('data', $payload)) {
        unlink($cacheFile);
        return null;
    }

    // Expired cache
    if ($payload['expires'] < time()) {
        if ($allowStale) {
            return $payload['data'];
        }
        unlink($cacheFile);
        return null;
    }

    // Valid cache
    return $payload['data'];
}
