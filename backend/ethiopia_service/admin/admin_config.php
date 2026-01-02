<?php
// backend/ethiopia_service/admin/admin_config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/log.php';

require_admin();

// ✅ Generate CSRF token
$csrfToken = generate_csrf_token();

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Handle save action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: admin_config.php?error=Invalid+CSRF+token");
        exit;
    }

    $apiKey        = trim($_POST['apiKey'] ?? '');
    $cacheDuration = intval($_POST['cacheDuration'] ?? 0);
    $rateLimit     = intval($_POST['rateLimit'] ?? 0);

    if ($apiKey === '' || $cacheDuration <= 0 || $rateLimit <= 0) {
        header("Location: admin_config.php?error=Invalid+input+values");
        exit;
    }

    try {
        $stmt = db()->prepare("REPLACE INTO system_config (config_key, config_value, updated_at) VALUES (:k,:v,NOW())");

        $stmt->execute([':k'=>'openweathermap', ':v'=>$apiKey]);
        $stmt->execute([':k'=>'cacheDuration', ':v'=>$cacheDuration]);
        $stmt->execute([':k'=>'rateLimit', ':v'=>$rateLimit]);

        log_event("System configuration updated", "INFO", [
            'module'=>'admin_config',
            'admin'=>$_SESSION['user']['id']
        ]);

        header("Location: admin_config.php?success=Settings+saved+successfully");
        exit;
    } catch (Exception $e) {
        header("Location: admin_config.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ✅ Load current settings
$settings = [
    'openweathermap' => '',
    'cacheDuration'  => 30,
    'rateLimit'      => 100
];
try {
    $stmt = db()->query("SELECT config_key, config_value FROM system_config");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['config_key']] = $row['config_value'];
    }
} catch (Exception $e) {
    log_event("Failed to load system config: " . $e->getMessage(), "ERROR", ['module'=>'admin_config']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Configure Settings</title>
    <link rel="stylesheet" href="/weather/frontend/style.css">
    <style>
        .admin-card { margin-bottom: 2rem; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; }
        .success-message { color: green; margin-top: 1rem; }
        .error-message { color: red; margin-top: 1rem; }
        label { display: block; margin: 0.5rem 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚙️ Admin Configuration</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-card">
        <h3>System Settings</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>OpenWeatherMap API Key
                <input type="text" id="apiKey" name="apiKey" value="<?= htmlspecialchars($settings['openweathermap']) ?>" required>
            </label>
            <label>Cache Duration (minutes)
                <input type="number" id="cacheDuration" name="cacheDuration" min="1" value="<?= htmlspecialchars($settings['cacheDuration']) ?>" required>
            </label>
            <label>Rate Limit (requests/minute)
                <input type="number" id="rateLimit" name="rateLimit" min="1" value="<?= htmlspecialchars($settings['rateLimit']) ?>" required>
            </label>
            <button type="submit">Save Settings</button>
        </form>
    </div>
</div>
</body>
</html>
