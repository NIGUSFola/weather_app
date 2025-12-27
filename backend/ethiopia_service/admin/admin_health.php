<?php
// backend/ethiopia_service/admin/admin_health.php
// Aggregated health check for all regions (HTML + JSON dual mode)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/log.php';

// ✅ Require admin role
require_admin();

// ✅ Load region health functions directly
require_once __DIR__ . '/../regions/oromia/health.php';
require_once __DIR__ . '/../regions/south/health.php';
require_once __DIR__ . '/../regions/amhara/health.php';
require_once __DIR__ . '/../regions/addis_ababa/health.php';

// ✅ Collect health data from all regions
$results = [
    oromia_health(),
    south_health(),
    amhara_health(),
    addis_ababa_health()
];

// ✅ JSON mode
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'     => 'OK',
        'regions'    => $results,
        'checked_at' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Health Checks</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
    <style>
        .badge-ok { color: #0a0; font-weight: bold; }
        .badge-fail { color: #c00; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        thead { background: #f0f0f0; }
        td, th { padding: 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background: #fafafa; }
        .summary { margin: 1em 0; padding: 0.5em; background: #eef; border: 1px solid #ccd; }
    </style>
</head>
<body>
<div class="container">
    <h1>❤️ Region Health Checks</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <div class="summary">
        <p>Total regions checked: <?= count($results) ?></p>
        <p>Last checked: <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="admin-card">
        <h3>System Health Overview</h3>
        <table>
            <thead>
                <tr><th>Region</th><th>Status</th><th>Components</th><th>Checked At</th></tr>
            </thead>
            <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['region']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'OK'): ?>
                            <span class="badge-ok">✅ OK</span>
                        <?php else: ?>
                            <span class="badge-fail">❌ FAIL</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($r['components'])): ?>
                            <ul>
                                <?php foreach ($r['components'] as $comp => $val): ?>
                                    <li>
                                        <?= htmlspecialchars($comp) ?>:
                                        <?php if (stripos($val, 'OK') !== false): ?>
                                            <span class="badge-ok"><?= htmlspecialchars($val) ?></span>
                                        <?php else: ?>
                                            <span class="badge-fail"><?= htmlspecialchars($val) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No component data</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['checked_at'] ?? date('Y-m-d H:i:s')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
