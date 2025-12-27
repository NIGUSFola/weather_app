<?php
// tests/auth_test.php
// Validate register, login, logout flows

require_once __DIR__ . '/../config/db.php';

$results = [];
function assertTrue($condition, $message) {
    global $results;
    echo ($condition ? "✅ PASS: " : "❌ FAIL: ") . $message . "\n";
    $results[] = $condition;
}

// --- Register test ---
$registerUrl = "http://localhost/weather/backend/auth/register.php";
$registerData = ['email'=>'testuser@example.com','password'=>'Test1234','name'=>'Test User'];
$opts = ['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded",
                  'content'=>http_build_query($registerData)]];
$res = file_get_contents($registerUrl, false, stream_context_create($opts));
$regJson = json_decode($res, true);
assertTrue(isset($regJson['success']), "Register endpoint responds");

// --- Login test ---
$loginUrl = "http://localhost/weather/backend/auth/login.php";
$loginData = ['email'=>'testuser@example.com','password'=>'Test1234'];
$opts['http']['content'] = http_build_query($loginData);
$res = file_get_contents($loginUrl, false, stream_context_create($opts));
$loginJson = json_decode($res, true);
assertTrue(isset($loginJson['success']), "Login endpoint responds");

// --- Logout test ---
$logoutUrl = "http://localhost/weather/backend/auth/logout.php";
$res = file_get_contents($logoutUrl);
$logoutJson = json_decode($res, true);
assertTrue(isset($logoutJson['success']), "Logout endpoint responds");

// --- Summary ---
$total = count($results);
$passed = count(array_filter($results));
echo "\n--- AUTH TEST SUMMARY ---\n";
echo "Passed: $passed / $total\n";
