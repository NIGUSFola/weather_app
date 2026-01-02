<?php
// frontend/dashboard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

require_admin(); // enforce admin access

// ✅ Flash messages
$flash   = $_SESSION['flash'] ?? null;
if ($flash) unset($_SESSION['flash']);
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Example system stats
$userCount = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$favCount  = (int) db()->query("SELECT COUNT(*) FROM favorites")->fetchColumn();

// ✅ CSRF token for API actions
$csrfToken = generate_csrf_token();

// ✅ Load API keys for current admin
$stmt = db()->prepare("SELECT key_name, api_key, created_at FROM api_keys WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $_SESSION['user']['id']]);
$apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Quick logs preview (last 5 entries)
$logs = [];
try {
    $stmt = db()->query("SELECT level, message, created_at FROM system_logs ORDER BY created_at DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// ✅ Quick metrics preview
$metrics = [];
try {
    $stmt = db()->query("SELECT metric, value FROM system_metrics ORDER BY updated_at DESC LIMIT 5");
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $metrics = [];
}

// ✅ Quick health snapshot
$healthData = [];
try {
    $healthJson = file_get_contents(__DIR__ . '/../backend/ethiopia_service/health.php');
    $healthData = json_decode($healthJson, true);
} catch (Exception $e) {
    $healthData = ['status' => 'degraded', 'checks' => [], 'time' => date('Y-m-d H:i:s')];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/style.css">
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <h2>⚙️ Admin Panel</h2>
        <nav>
            <ul>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_dashboard.php">📊 System Overview</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_alerts.php">⚠️ Alerts Management</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_logs.php">📝 System Logs</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_metrics.php">📈 Metrics</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_config.php">🔧 Configuration</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_cache.php">🗄️ Cache Management</a></li>
                <li><a href="/weather/backend/ethiopia_service/admin/admin_health.php">🩺 Health Checks</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <h1>🌤️ Admin Dashboard</h1>

        <!-- Feedback -->
        <?php if (!empty($flash)): ?><div class="success-message"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Admin welcome -->
        <div class="admin-card">
          <h2>Welcome, Admin <?= htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></h2>
          <p>Here’s your central administrative dashboard. Use the sidebar to access each module.</p>
        </div>

        <!-- Quick system stats -->
        <div class="admin-card">
          <h2>📊 System Monitoring</h2>
          <p>Total Users: <?= htmlspecialchars($userCount); ?></p>
          <p>Total Favorites Saved: <?= htmlspecialchars($favCount); ?></p>
        </div>

        <!-- API Key Management -->
        <div class="admin-card">
          <h2>🔑 API Key Management</h2>
          <?php if (!empty($apiKeys)): ?>
            <table class="api-keys">
              <thead><tr><th>Key Name</th><th>API Key</th><th>Created At</th><th>Action</th></tr></thead>
              <tbody>
              <?php foreach ($apiKeys as $key): ?>
                <tr>
                  <td><?= htmlspecialchars($key['key_name']); ?></td>
                  <td><?= htmlspecialchars($key['api_key']); ?></td>
                  <td><?= htmlspecialchars($key['created_at']); ?></td>
                  <td>
                    <form method="POST" action="/weather/backend/ethiopia_service/api.php?action=delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="key" value="<?= htmlspecialchars($key['api_key']); ?>">
                      <button type="submit" class="danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?><p>No API keys found.</p><?php endif; ?>

          <form method="POST" action="/weather/backend/ethiopia_service/api.php?action=create" class="create-key-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <label for="key_name">Key Name:</label>
            <input type="text" id="key_name" name="key_name" placeholder="Optional name">
            <button type="submit">Generate New Key</button>
          </form>
        </div>

        <!-- Regional Weather Feed -->
        <div class="admin-card">
          <h2>🌍 Regional Forecasts & Alerts</h2>
          <div id="adminFeed"><p>Loading regional data...</p></div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
// ✅ Load regional forecasts & alerts for admin
async function loadAdminFeed() {
  try {
    const [forecastRes, alertsRes] = await Promise.all([
      fetch("/weather/backend/ethiopia_service/forecast.php", {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      }),
      fetch("/weather/backend/ethiopia_service/alerts.php", {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      })
    ]);

    const forecastData = await forecastRes.json();
    const alertsData   = await alertsRes.json();

    const container = document.getElementById("adminFeed");
    container.innerHTML = '';

    for (const [region, forecastInfo] of Object.entries(forecastData.regions)) {
      const div = document.createElement('div');
      div.className = 'region-card';
      div.innerHTML = `<h3>${region} (${forecastInfo.city})</h3>`;

      // Forecast snapshot
      if (forecastInfo.forecast && forecastInfo.forecast.length > 0) {
        const f = forecastInfo.forecast[0];
        div.innerHTML += `<p><strong>Forecast:</strong> ${f.min_temp}°C – ${f.max_temp}°C, ${f.condition}</p>`;
      } else {
        div.innerHTML += "<p>No forecast available.</p>";
      }

      // Alerts snapshot
      const alertInfo = alertsData.regions[region];
      if (alertInfo && Array.isArray(alertInfo.alerts) && alertInfo.alerts.length > 0) {
        alertInfo.alerts.forEach(alert => {
          div.innerHTML += `<p><strong>${alert.event}</strong> (${alert.severity.toUpperCase()})<br>${alert.description}</p>`;
        });
      } else {
        div.innerHTML += "<p>No active alerts.</p>";
      }

      container.appendChild(div);
    }
  } catch (err) {
    document.getElementById("adminFeed").innerHTML =
      `<div class="error-message">Error loading feed: ${err.message}</div>`;
  }
}
document.addEventListener("DOMContentLoaded", loadAdminFeed);
</script>
</body>
</html>
