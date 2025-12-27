<?php
// tests/sample_test.php
// Structured validation suite for Multi-Region Weather App

require_once __DIR__ . '/../config/db.php';

$results = [];
function assertTrue($condition, $message) {
    global $results;
    if ($condition) {
        echo "✅ PASS: $message\n";
        $results[] = true;
    } else {
        echo "❌ FAIL: $message\n";
        $results[] = false;
    }
}

try {
    $pdo = db();
    assertTrue($pdo instanceof PDO, "Connected to DB");

    // Essential tables
    $tables = ['users','cities','favorites','weather_cache','api_requests','logs','distributed_locks'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            assertTrue($count !== false, "Table '$table' exists (rows: $count)");
        } catch (Exception $e) {
            assertTrue(false, "Table '$table' missing: " . $e->getMessage());
        }
    }

    // Admin account
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE role='admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    assertTrue($admin !== false, "Admin account exists");

    // Seeded cities
    $stmt = $pdo->query("SELECT COUNT(*) FROM cities");
    $cityCount = $stmt->fetchColumn();
    assertTrue($cityCount > 0, "Cities seeded ($cityCount found)");

    // Favorites test (insert + delete)
    $userId = $admin['id'] ?? 1;
    $cityId = 1;
    $pdo->prepare("INSERT IGNORE INTO favorites (user_id, city_id, created_at) VALUES (?, ?, NOW())")
        ->execute([$userId, $cityId]);
    $favCount = $pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id=$userId AND city_id=$cityId")->fetchColumn();
    assertTrue($favCount > 0, "Favorite added for user $userId");

    $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND city_id=?")->execute([$userId, $cityId]);
    $favCount = $pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id=$userId AND city_id=$cityId")->fetchColumn();
    assertTrue($favCount == 0, "Favorite removed for user $userId");

    // Health endpoint
    $healthJson = @file_get_contents(__DIR__ . '/../backend/ethiopia_service/health.php');
    $healthData = json_decode($healthJson, true);
    assertTrue(isset($healthData['status']), "Health endpoint returns status");

    // Forecast endpoint (city_id=1)
    $forecastJson = @file_get_contents(__DIR__ . '/../backend/ethiopia_service/forecast.php?city_id=1');
    $forecastData = json_decode($forecastJson, true);
    assertTrue(isset($forecastData['data']['days']), "Forecast endpoint returns days array");
    assertTrue(isset($forecastData['city']), "Forecast endpoint includes city");

    // Aggregator endpoint
    $aggJson = @file_get_contents(__DIR__ . '/../backend/aggregator/merge_feeds.php');
    $aggData = json_decode($aggJson, true);
    assertTrue(isset($aggData['summary']['total_alerts']), "Aggregator returns total_alerts");
    assertTrue(isset($aggData['regions']['Oromia']), "Aggregator includes Oromia region");
    assertTrue(isset($aggData['regions']['Addis Ababa']), "Aggregator includes Addis Ababa region");

} catch (Exception $e) {
    assertTrue(false, "DB connection failed: " . $e->getMessage());
}

// Summary
$total = count($results);
$passed = count(array_filter($results));
echo "\n--- TEST SUMMARY ---\n";
echo "Passed: $passed / $total\n";
