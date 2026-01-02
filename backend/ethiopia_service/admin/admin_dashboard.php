<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/csrf.php';

require_admin();
$csrfToken = generate_csrf_token();

function safeCount(string $sql): int {
    try {
        $stmt = db()->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        log_event("Count query failed: " . $e->getMessage(), "ERROR");
        return 0;
    }
}

// Summary counts
$userCount   = safeCount("SELECT COUNT(*) FROM users");
$favCount    = safeCount("SELECT COUNT(*) FROM favorites");
$cacheCount  = safeCount("SELECT COUNT(*) FROM weather_cache");
$totalReqs   = safeCount("SELECT COUNT(*) FROM api_requests WHERE DATE(requested_at)=CURDATE()");
$totalAlerts = safeCount("SELECT COUNT(*) FROM alerts WHERE DATE(created_at)=CURDATE()");
$uptime      = "99.98% (placeholder)";

// Requests per hour
$requestsPerHour = [];
try {
    $stmt = db()->query("
        SELECT HOUR(requested_at) AS hr, COUNT(*) AS cnt
        FROM api_requests
        WHERE DATE(requested_at)=CURDATE()
        GROUP BY hr ORDER BY hr
    ");
    $requestsPerHour = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Active users by region
$activeUsersByRegion = [];
try {
    $stmt = db()->query("
        SELECT r.name AS region, COUNT(DISTINCT ar.user_id) AS users
        FROM api_requests ar
        JOIN cities c ON ar.city_id=c.id
        JOIN regions r ON c.region_id=r.id
        WHERE ar.requested_at > (NOW()-INTERVAL 1 DAY)
        GROUP BY r.name ORDER BY users DESC
    ");
    $activeUsersByRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Logs snapshot
$logs = [];
try {
    $stmt = db()->query("SELECT created_at, level, message FROM system_logs ORDER BY created_at DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Multi-Region Weather</title>
<link rel="stylesheet" href="/weather/frontend/style.css">
<style>
.badge { display:inline-block; padding:6px 12px; border-radius:6px; font-weight:bold; margin:4px; }
.badge-uptime { background:#2ecc71; color:white; }
.badge-requests { background:#3498db; color:white; }
.badge-alerts { background:#e67e22; color:white; }
table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
th, td { border: 1px solid #ccc; padding: 6px; text-align:left; }
th { background:#f0f0f0; }
.log-info { color:green; }
.log-warn { color:orange; }
.log-error { color:red; }
.status-ok { color:green; font-weight:bold; }
.status-fail { color:red; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<h1>🛠 Admin Dashboard</h1>
<?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

<section>
  <h2>✅ Summary</h2>
  <span class="badge badge-uptime">Uptime: <?= htmlspecialchars($uptime) ?></span>
  <span class="badge badge-requests">Total Requests: <?= htmlspecialchars($totalReqs) ?></span>
  <span class="badge badge-alerts">Alerts Triggered: <?= htmlspecialchars($totalAlerts) ?></span>
</section>

<section>
  <h2>⏱ API Requests Per Hour (Today)</h2>
  <?php if ($requestsPerHour): ?>
  <table><tr><th>Hour</th><th>Requests</th></tr>
  <?php foreach ($requestsPerHour as $row): ?>
    <tr><td><?= htmlspecialchars($row['hr']) ?>:00</td><td><?= htmlspecialchars($row['cnt']) ?></td></tr>
  <?php endforeach; ?>
  </table>
  <?php else: ?><p>No request data available.</p><?php endif; ?>
</section>

<section>
  <h2>👥 Active Users by Region (Last 24h)</h2>
  <?php if ($activeUsersByRegion): ?>
  <table><tr><th>Region</th><th>Active Users</th></tr>
  <?php foreach ($activeUsersByRegion as $row): ?>
    <tr><td><?= htmlspecialchars($row['region']) ?></td><td><?= htmlspecialchars($row['users']) ?></td></tr>
  <?php endforeach; ?>
  </table>
  <?php else: ?><p>No active user data available.</p><?php endif; ?>
</section>

<section>
  <h2>💡 Health Check</h2>
  <button type="button" onclick="runHealthCheck()">Run Health Check</button>
  <div id="healthResult">Click the button to run health check.</div>
</section>

<section>
  <h2>📝 Recent Logs</h2>
  <?php if ($logs): ?>
  <table><tr><th>Time</th><th>Level</th><th>Message</th></tr>
  <?php foreach ($logs as $log): 
    $cls = ($log['level']==='INFO')?'log-info':(($log['level']==='WARN')?'log-warn':'log-error'); ?>
    <tr><td><?= htmlspecialchars($log['created_at']) ?></td>
        <td class="<?= $cls ?>"><?= htmlspecialchars($log['level']) ?></td>
        <td><?= htmlspecialchars($log['message']) ?></td></tr>
  <?php endforeach; ?>
  </table>
  <?php else: ?><p>No logs available.</p><?php endif; ?>
</section>
</div>

<script>
async function runHealthCheck() {
  try {
    const res = await fetch('/weather/backend/ethiopia_service/admin/admin_health.php?format=json');
    const data = await res.json();
    if (!data || !data.regions) throw new Error("Invalid JSON");
    let html = '<table><tr><th>City</th><th>Status</th><th>Components</th><th>Checked At</th></tr>';
    data.regions.forEach(r=>{
      let statusClass = (r.status==='OK')?'status-ok':'status-fail';
      let comps = Object.entries(r.components).map(([c,v])=>{
        let compClass = (v.includes('OK'))?'status-ok':'status-fail';
        return `<span class="${compClass}">${c}: ${v}</span>`;
      }).join('<br>');
      html += `<tr><td>${r.city||r.region}</td><td class="${statusClass}">${r.status}</td><td>${comps}</td><td>${r.checked_at}</td></tr>`;
    });
    html += '</table>';
    document.getElementById('healthResult').innerHTML = html;
  } catch(err) {
    document.getElementById('healthResult').textContent = 'Health check failed: '+err.message;
  }
}
</script>
</body>
</html>
