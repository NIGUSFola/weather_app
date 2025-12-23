<?php
// backend/ethiopia_service/admin/admin_logs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../../config/db.php';

require_admin();

// ✅ Fetch recent logs (last 100 entries)
try {
    $stmt = db()->query("SELECT created_at, level, message FROM system_logs ORDER BY created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
    log_event("Failed to fetch logs: " . $e->getMessage(), "ERROR", ['module'=>'admin_logs']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Logs - Weather Aggregator</title>
    <link rel="stylesheet" href="/weather_app/frontend/style.css">
</head>
<body>
<div class="container">
    <h1>📜 System Logs</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <main>
        <section>
            <h2>📝 Recent Logs</h2>
            <?php if (empty($logs)): ?>
                <p>No logs available.</p>
            <?php else: ?>
            <table class="logs">
                <thead>
                  <tr><th>Time</th><th>Level</th><th>Message</th></tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
