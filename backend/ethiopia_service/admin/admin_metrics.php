<?php
// backend/ethiopia_service/admin/admin_metrics.php
// Admin interface for viewing system metrics (no charts, tables + badges)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../../config/db.php';

require_admin();

$csrfToken = generate_csrf_token();

// --- Safe query helper ---
function safeQuery(string $sql): int {
    try {
        $stmt = db()->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        log_event("Metrics query failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_metrics']);
        return 0;
    }
}

// ✅ API requests per hour (today)
$requestsPerHour = [];
try {
    $stmt = db()->query("
        SELECT HOUR(requested_at) AS hr, COUNT(*) AS cnt
        FROM api_requests
        WHERE DATE(requested_at) = CURDATE()
        GROUP BY hr
        ORDER BY hr
    ");
    $requestsPerHour = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    log_event("Hourly requests query failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_metrics']);
}

// ✅ Active users by region (last 24h)
$activeUsersByRegion = [];
try {
    $stmt = db()->query("
        SELECT r.name AS region, COUNT(DISTINCT ar.user_id) AS users
        FROM api_requests ar
        JOIN cities c ON ar.city_id = c.id
        JOIN regions r ON c.region_id = r.id
        WHERE ar.requested_at > (NOW() - INTERVAL 1 DAY)
        GROUP BY r.name
        ORDER BY users DESC
    ");
    $activeUsersByRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    log_event("Active users by region query failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_metrics']);
}

// ✅ Summary badges
$totalRequests = safeQuery("SELECT COUNT(*) FROM api_requests WHERE DATE(requested_at) = CURDATE()");
$totalAlerts   = safeQuery("SELECT COUNT(*) FROM alerts WHERE DATE(created_at) = CURDATE()");
$systemUptime  = "99.98% (placeholder)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Metrics - Ethiopia Weather</title>
  <link rel="stylesheet" href="/weather/frontend/style.css">
  <style>
    .badge { display:inline-block; padding:6px 12px; border-radius:6px; font-weight:bold; margin:4px; }
    .badge-uptime { background:#2ecc71; color:white; }
    .badge-requests { background:#3498db; color:white; }
    .badge-alerts { background:#e67e22; color:white; }
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align:left; }
    th { background: #f0f0f0; }
  </style>
</head>
<body>
<div class="container">
  <h1>📊 System Metrics</h1>
  <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

  <!-- Summary badges -->
  <section>
    <h2>✅ Summary</h2>
    <span class="badge badge-uptime">Uptime: <?= htmlspecialchars($systemUptime) ?></span>
    <span class="badge badge-requests">Total Requests: <?= htmlspecialchars($totalRequests) ?></span>
    <span class="badge badge-alerts">Alerts Triggered: <?= htmlspecialchars($totalAlerts) ?></span>
  </section>

  <!-- API requests per hour -->
  <section>
    <h2>⏱ API Requests Per Hour (Today)</h2>
    <?php if (!empty($requestsPerHour)): ?>
      <table>
        <tr><th>Hour</th><th>Requests</th></tr>
        <?php foreach ($requestsPerHour as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['hr']) ?>:00</td>
            <td><?= htmlspecialchars($row['cnt']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>No request data available for today.</p>
    <?php endif; ?>
  </section>

  <!-- Active users by region -->
  <section>
    <h2>👥 Active Users by Region (Last 24h)</h2>
    <?php if (!empty($activeUsersByRegion)): ?>
      <table>
        <tr><th>Region</th><th>Active Users</th></tr>
        <?php foreach ($activeUsersByRegion as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['region']) ?></td>
            <td><?= htmlspecialchars($row['users']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>No active user data available.</p>
    <?php endif; ?>
  </section>
</div>
</body>
</html>
