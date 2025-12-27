<?php
// backend/ethiopia_service/cache_test.php
// Simple cache test harness

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/cache.php';

$key = 'test_cache_key';

// 1. Set cache
cache_set($key, ['message' => 'Hello Nigus!', 'time' => date('Y-m-d H:i:s')], 60);

// 2. Get cache (fresh)
$fresh = cache_get($key);

// 3. Get cache (allow stale)
$stale = cache_get($key, true);

echo json_encode([
    'set'   => true,
    'fresh' => $fresh,
    'stale' => $stale,
    'time'  => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
