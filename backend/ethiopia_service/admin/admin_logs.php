<?php
// backend/ethiopia_service/admin/admin_logs.php
// Admin interface for viewing and filtering system logs

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../../config/db.php';

require_admin();

$csrfToken = generate_csrf_token();

// ✅ Filters from query string
$level   = $_GET['level']   ?? '';
$region  = $_GET['region']  ?? '';
$from    = $_GET['from']    ?? '';
$to      = $_GET['to']      ?? '';

$sql = "SELECT created_at, level, message, module, region 
        FROM system_logs 
        WHERE 1=1";
$params = [];

if ($level) {
    $sql .= " AND level = :level";
    $params[':level'] = $level;
}
if ($region) {
    $sql .= " AND region = :region";
    $params[':region'] = $region;
}
if ($from) {
    $sql .= " AND created_at >= :from";
    $params[':from'] = $from;
}
if ($to) {
    $sql .= " AND created_at <= :to";
    $params[':to'] = $to;
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
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
    <title>Admin Logs - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/style.css">
    <style>
        table.logs { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        table.logs th, table.logs td { border: 1px solid #ccc; padding: 8px; }
        table.logs th { background: #f0f0f0; }
        .log-info { color: green; font-weight: bold; }
        .log-warn { color: orange; font-weight: bold; }
        .log-error { color: red; font-weight: bold; }
        form.filters { margin: 1rem 0; }
        form.filters label { margin-right: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>📜 System Logs</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <!-- Filters -->
    <form method="GET" class="filters">
        <label>Level:
            <select name="level">
                <option value="">All</option>
                <option value="INFO" <?= $level==='INFO'?'selected':'' ?>>INFO</option>
                <option value="WARN" <?= $level==='WARN'?'selected':'' ?>>WARN</option>
                <option value="ERROR" <?= $level==='ERROR'?'selected':'' ?>>ERROR</option>
            </select>
        </label>
        <label>Region:
            <input type="text" name="region" value="<?= htmlspecialchars($region) ?>" placeholder="e.g. Oromia">
        </label>
        <label>From:
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        </label>
        <label>To:
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        </label>
        <button type="submit">Filter</button>
    </form>

    <section>
        <h2>📝 Logs (Latest 200)</h2>
        <?php if (empty($logs)): ?>
            <p>No logs available for selected filters.</p>
        <?php else: ?>
        <table class="logs">
            <thead>
                <tr><th>Time</th><th>Level</th><th>Region</th><th>Module</th><th>Message</th></tr>
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
                    <td><?= htmlspecialchars($log['region'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['module'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
