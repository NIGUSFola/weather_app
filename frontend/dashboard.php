<?php
// frontend/dashboard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

// ✅ Require admin role
require_admin();

// ✅ Flash messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Example system stats
$userCount = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$favCount  = (int) db()->query("SELECT COUNT(*) FROM favorites")->fetchColumn();

// ✅ CSRF token for logout
$logoutToken = generate_csrf_token();

// ✅ Fetch unified data from aggregator
$aggregatorData = [];
try {
    $data = file_get_contents(__DIR__ . '/../backend/aggregator/merge_feeds.php');
    $aggregatorData = json_decode($data, true);
} catch (Exception $e) {
    $aggregatorData = ['error' => $e->getMessage()];
}

// ✅ Fetch health status JSON
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="layout">
   

    <main class="page-dashboard">
      <section class="dashboard-section">
        <h1>🌤️ Admin Dashboard</h1>

        <!-- Feedback -->
        <?php if (!empty($flash)): ?><div class="success-message"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Admin welcome -->
        <div class="card">
          <h2>Welcome, Admin <?= htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></h2>
          <p>Here’s your administrative weather dashboard.</p>
        </div>

        <!-- System stats -->
        <div class="section">
          <h3>📊 System Monitoring</h3>
          <p>Total Users: <?= htmlspecialchars($userCount); ?></p>
          <p>Total Favorites Saved: <?= htmlspecialchars($favCount); ?></p>
        </div>

        <!-- Summary -->
        <div class="section">
          <h3>📌 Summary</h3>
          <?php if (!empty($aggregatorData['summary'])): ?>
            <div class="summary-box">
              <p><strong>Total Active Alerts:</strong> <?= htmlspecialchars($aggregatorData['summary']['total_alerts']); ?></p>
              <p><strong>Generated At:</strong> <?= htmlspecialchars($aggregatorData['summary']['generated_at']); ?></p>
            </div>
          <?php else: ?><p>No summary data available.</p><?php endif; ?>
        </div>

        <!-- Alerts -->
        <div class="section alerts-overlay">
          <h3>⚠️ Unified Regional Alerts</h3>
          <?php if (!empty($aggregatorData['regions'])): ?>
            <?php foreach ($aggregatorData['regions'] as $region => $info): ?>
              <div class="alert-card">
                <h4><?= htmlspecialchars($region); ?> (<?= htmlspecialchars($info['city'] ?? ''); ?>)</h4>
                <?php if (!empty($info['alerts'])): ?>
                  <?php foreach ($info['alerts'] as $alert): ?>
                    <p><strong><?= htmlspecialchars($alert['event']); ?></strong>
                       (<?= htmlspecialchars($alert['start']); ?> → <?= htmlspecialchars($alert['end']); ?>)
                       <span class="severity-badge severity-<?= htmlspecialchars($alert['severity'] ?? 'moderate'); ?>">
                         <?= strtoupper(htmlspecialchars($alert['severity'] ?? 'moderate')); ?>
                       </span>
                    </p>
                  <?php endforeach; ?>
                <?php else: ?><p><em>No alerts available</em></p><?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?><p>No alerts data available.</p><?php endif; ?>
        </div>

        <!-- Forecast -->
        <div class="section">
          <h3>🌦️ Regional Forecasts</h3>
          <?php if (!empty($aggregatorData['regions'])): ?>
            <table>
              <thead><tr><th>Region</th><th>City</th><th>Forecast (next entries)</th></tr></thead>
              <tbody>
                <?php foreach ($aggregatorData['regions'] as $region => $info): ?>
                  <tr>
                    <td><?= htmlspecialchars($region); ?></td>
                    <td><?= htmlspecialchars($info['city'] ?? ''); ?></td>
                    <td>
                      <?php if (!empty($info['forecast'])): ?>
                        <ul>
                          <?php foreach (array_slice($info['forecast'], 0, 3) as $entry): ?>
                            <li><?= htmlspecialchars($entry['datetime']); ?>:
                              <?= htmlspecialchars($entry['weather']); ?>,
                              Temp <?= htmlspecialchars($entry['temp']); ?>K
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php else: ?><em>No forecast available</em><?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?><p>No forecast data available.</p><?php endif; ?>
        </div>

        <!-- Health -->
        <div class="section">
          <h3>🩺 System Health</h3>
          <?php if (!empty($healthData['checks'])): ?>
            <table>
              <thead><tr><th>Check</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($healthData['checks'] as $check => $value): ?>
                  <tr>
                    <td><?= htmlspecialchars(strtoupper($check)); ?></td>
                    <td class="<?= $value ? 'status-ok' : 'status-fail'; ?>">
                      <?= $value ? 'OK' : 'FAIL'; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr>
                  <td><strong>Overall</strong></td>
                  <td class="<?= ($healthData['status'] === 'ok') ? 'status-ok' : 'status-fail'; ?>">
                    <?= htmlspecialchars(strtoupper($healthData['status'])); ?> (<?= htmlspecialchars($healthData['time']); ?>)
                  </td>
                </tr>
              </tbody>
            </table>
          <?php else: ?><p>No health data available.</p><?php endif; ?>
        </div>

        <!-- Admin links -->
        <div class="section">
          <h3>👥 User Management</h3>
          <p><a href="../backend/ethiopia_service/admin/admin_dashboard.php">View All Users</a></p>
        </div>
        <div class="section">
          <h3>⚙️ Alerts Management</h3>
          <p><a href="../backend/ethiopia_service/admin/admin_alerts.php">Configure Alerts</a></p>
        </div>
      </section>
    </main>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </div>
</body>
</html>
