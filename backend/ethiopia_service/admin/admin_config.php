<?php
// backend/ethiopia_service/admin/admin_config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/csrf.php';

require_admin();

// ✅ Generate CSRF token if missing
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Configure Settings</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
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

    <div class="admin-card">
        <h3>System Settings</h3>
        <form id="configForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>OpenWeatherMap API Key
                <input type="text" id="apiKey" name="apiKey" required>
            </label>
            <label>Cache Duration (minutes)
                <input type="number" id="cacheDuration" name="cacheDuration" min="1" required>
            </label>
            <label>Rate Limit (requests/minute)
                <input type="number" id="rateLimit" name="rateLimit" min="1" required>
            </label>
            <button type="submit">Save Settings</button>
        </form>
        <div id="configMessage"></div>
    </div>


</div>

<script>
async function loadSettings() {
    try {
        const res = await fetch('config_api.php?action=get');
        const data = await res.json();
        if (data.settings) {
            document.getElementById('apiKey').value = data.settings.openweathermap || '';
            document.getElementById('cacheDuration').value = data.settings.cacheDuration || 30;
            document.getElementById('rateLimit').value = data.settings.rateLimit || 100;
        } else if (data.error) {
            document.getElementById('configMessage').innerHTML = '<div class="error-message">' + data.error + '</div>';
        }
    } catch (err) {
        document.getElementById('configMessage').innerHTML = '<div class="error-message">Failed to load settings.</div>';
    }
}

document.getElementById('configForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('config_api.php?action=save', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('configMessage').innerHTML = '<div class="success-message">' + data.message + '</div>';
        } else {
            document.getElementById('configMessage').innerHTML = '<div class="error-message">' + (data.error || 'Failed to save settings') + '</div>';
        }
    } catch (err) {
        document.getElementById('configMessage').innerHTML = '<div class="error-message">Error saving settings.</div>';
    }
});

loadSettings();
</script>
</body>
</html>
