<?php
// backend/ethiopia_service/admin/admin_dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/csrf.php';

require_admin();

$csrfToken = generate_csrf_token();

function getCount(string $table): int {
    try {
        $stmt = db()->query("SELECT COUNT(*) FROM {$table}");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        log_event("Count query failed for {$table}: " . $e->getMessage(), "ERROR", ['module'=>'admin_dashboard']);
        return 0;
    }
}

$userCount  = getCount('users');
$favCount   = getCount('favorites');
$cacheCount = getCount('weather_cache');

try {
    $stmt = db()->query("SELECT COUNT(*) FROM api_requests WHERE requested_at > (NOW() - INTERVAL 1 DAY)");
    $apiRequests24h = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $apiRequests24h = 0;
    log_event("API requests count failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_dashboard']);
}

try {
    $stmt = db()->query("SELECT created_at, level, message FROM system_logs ORDER BY created_at DESC LIMIT 50");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
    log_event("Log fetch failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_dashboard']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Multi-Region Weather</title>
    <link rel="stylesheet" href="../../../frontend/style.css">
    <style>
        .logs { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .logs th, .logs td { border: 1px solid #ccc; padding: 0.5rem; }
        .log-info { color: green; }
        .log-warn { color: orange; }
        .log-error { color: red; }
        .status-ok { color: green; font-weight: bold; }
        .status-degraded { color: orange; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🛠 Admin Dashboard</h1>

    <!-- ✅ Include fixed navigation -->
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <section>
        <h2>📊 System Metrics</h2>
        <ul>
            <li>Total Users: <?= htmlspecialchars($userCount) ?></li>
            <li>Total Favorites: <?= htmlspecialchars($favCount) ?></li>
            <li>API Requests (last 24h): <?= htmlspecialchars($apiRequests24h) ?></li>
            <li>Cache Entries: <?= htmlspecialchars($cacheCount) ?></li>
        </ul>
    </section>

    <section id="logs">
        <h2>📝 Recent Logs</h2>
        <?php if (empty($logs)): ?>
            <p>No logs available.</p>
        <?php else: ?>
        <table class="logs">
            <tr><th>Time</th><th>Level</th><th>Message</th></tr>
            <?php foreach ($logs as $log): 
                $levelClass = '';
                if ($log['level'] === 'INFO') $levelClass = 'log-info';
                elseif ($log['level'] === 'WARN') $levelClass = 'log-warn';
                elseif ($log['level'] === 'ERROR') $levelClass = 'log-error';
            ?>
                <tr>
                    <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                    <td class="<?= $levelClass ?>"><?= htmlspecialchars($log['level'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </section>

    <section>
        <h2>💡 Health Check</h2>
        <button type="button" onclick="runHealthCheck()">Run Health Check</button>
        <div id="healthResult">Click the button to run health check.</div>
    </section>


</div>

<script>
function runHealthCheck() {
    // ✅ Correct path to health.php
    fetch('../../ethiopia_service/health.php')
        .then(res => res.json())
        .then(data => {
            let statusClass = '';
            if (data.status === 'ok') statusClass = 'status-ok';
            else if (data.status === 'degraded') statusClass = 'status-degraded';
            else statusClass = 'status-fail';

            document.getElementById('healthResult').innerHTML = `
                <ul>
                  <li>Database: ${data.checks.db ? '✅ OK' : '❌ Failed'}</li>
                  <li>API Key: ${data.checks.api_key ? '✅ OK' : '❌ Failed'}</li>
                  <li>Session: ${data.checks.session ? '✅ OK' : '❌ Failed'}</li>
                  <li>Status: <span class="${statusClass}">${data.status.toUpperCase()}</span></li>
                  <li>Time: ${data.time}</li>
                </ul>`;
        })
        .catch(err => {
            document.getElementById('healthResult').textContent = 'Health check failed: ' + err;
        });
}
</script>
</body>
</html>
