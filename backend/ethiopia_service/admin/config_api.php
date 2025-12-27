<?php
// backend/ethiopia_service/admin/config_api.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../../config/db.php';

require_admin();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? null;

if ($action === 'get') {
    try {
        $stmt = db()->query("SELECT api_key, cache_duration, rate_limit FROM system_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['settings' => $settings ?: []]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load settings: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $apiKey        = $_POST['apiKey'] ?? '';
    $cacheDuration = (int)($_POST['cacheDuration'] ?? 30);
    $rateLimit     = (int)($_POST['rateLimit'] ?? 100);

    try {
        // If no row exists, insert. Otherwise update the first row.
        $stmt = db()->query("SELECT id FROM system_settings LIMIT 1");
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $stmt = db()->prepare("UPDATE system_settings SET api_key = :api_key, cache_duration = :cache_duration, rate_limit = :rate_limit WHERE id = :id");
            $stmt->execute([
                ':api_key' => $apiKey,
                ':cache_duration' => $cacheDuration,
                ':rate_limit' => $rateLimit,
                ':id' => $existing
            ]);
        } else {
            $stmt = db()->prepare("INSERT INTO system_settings (api_key, cache_duration, rate_limit) VALUES (:api_key, :cache_duration, :rate_limit)");
            $stmt->execute([
                ':api_key' => $apiKey,
                ':cache_duration' => $cacheDuration,
                ':rate_limit' => $rateLimit
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error saving settings: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
