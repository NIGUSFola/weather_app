<?php
// backend/test_db.php
// Quick diagnostic script to verify DB connectivity and essential tables

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = db();
    echo "✅ Connected to DB\n\n";

    // Check tables
    $tables = ['users','cities','favorites','weather_cache','api_requests','logs','distributed_locks'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Table '$table' exists, row count: $count\n";
        } catch (Exception $e) {
            echo "❌ Table '$table' missing or inaccessible: " . $e->getMessage() . "\n";
        }
    }

    // Check admin account
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE role='admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin) {
        echo "\n✅ Admin account found: {$admin['email']} (role: {$admin['role']})\n";
    } else {
        echo "\n❌ No admin account found. Run seed.php to create one.\n";
    }

    // Check seeded cities
    $stmt = $pdo->query("SELECT name, region FROM cities ORDER BY id LIMIT 5");
    $cities = $stmt->fetchAll();
    if ($cities) {
        echo "\n✅ Cities seeded:\n";
        foreach ($cities as $c) {
            echo "   - {$c['name']} ({$c['region']})\n";
        }
    } else {
        echo "\n❌ No cities found. Run seed.php to populate.\n";
    }

} catch (Exception $e) {
    echo "❌ DB connection failed: " . $e->getMessage() . "\n";
}
