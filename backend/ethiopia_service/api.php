<?php
// backend/ethiopia_service/api.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/log.php';

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    header("Location: /weather_app/frontend/login.php?error=Please+login+first");
    exit;
}

// ✅ CSRF check helper
function check_csrf(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf()) {
        header("Location: /weather_app/frontend/api.php?error=Invalid+CSRF+token");
        exit;
    }

    try {
        // Generate new API key
        $newKey = bin2hex(random_bytes(16));
        $keyName = "User Key " . date('Y-m-d H:i:s');

        $stmt = db()->prepare("INSERT INTO api_keys (user_id, key_name, api_key, created_at) VALUES (:user_id, :key_name, :api_key, NOW())");
        $stmt->execute([
            ':user_id' => $userId,
            ':key_name' => $keyName,
            ':api_key' => $newKey
        ]);

        log_event("API key created", "INFO", ['module'=>'api','user_id'=>$userId,'key_name'=>$keyName]);
        header("Location: /weather_app/frontend/api.php?success=API+key+generated");
        exit;
    } catch (Exception $e) {
        log_event("API key creation failed: " . $e->getMessage(), "ERROR", ['module'=>'api','user_id'=>$userId]);
        header("Location: /weather_app/frontend/api.php?error=Key+generation+failed");
        exit;
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf()) {
        header("Location: /weather_app/frontend/api.php?error=Invalid+CSRF+token");
        exit;
    }
    $key = $_POST['key'] ?? '';
    $stmt = db()->prepare("DELETE FROM api_keys WHERE user_id = :user_id AND api_key = :api_key");
    $stmt->execute([':user_id' => $userId, ':api_key' => $key]);

    log_event("API key deleted", "INFO", ['module'=>'api','user_id'=>$userId,'api_key'=>$key]);
    header("Location: /weather_app/frontend/api.php?success=API+key+deleted");
    exit;
}

if ($action === 'list') {
    header('Content-Type: application/json');
    $stmt = db()->prepare("SELECT key_name, api_key, created_at FROM api_keys WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'keys' => $keys,
        'csrf_token' => $_SESSION['csrf_token'] ?? ''
    ], JSON_PRETTY_PRINT);
    exit;
}
