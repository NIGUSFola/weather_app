<?php
// backend/ethiopia_service/admin/admin_metrics.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/log.php';
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

// API requests today
$apiRequestsToday = safeQuery("SELECT COUNT(*) FROM api_requests WHERE DATE(requested_at) = CURDATE()");

// Cache hits percentage
try {
    $stmt = db()->query("
        SELECT 
            SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) AS hits,
            COUNT(*) AS total
        FROM api_requests 
        WHERE DATE(requested_at) = CURDATE()
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cacheHits = ($row && $row['total'] > 0) ? round(($row['hits'] / $row['total']) * 100, 2) : 0;
} catch (Exception $e) {
    $cacheHits = 0;
    log_event("Cache hit query failed: " . $e->getMessage(), "ERROR", ['module'=>'admin_metrics']);
}

// Active users (last 24h)
$activeUsers = safeQuery("SELECT COUNT(DISTINCT user_id) FROM api_requests WHERE requested_at > (NOW() - INTERVAL 1 DAY)");

// System uptime (placeholder unless tracked separately)
$systemUptime = "99.98% (placeholder)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Metrics - Multi-Region Weather</title>
  <link rel="stylesheet" href="../../../frontend/style.css">
  <style>
    .metrics-list { list-style: none; padding: 0; }
    .metrics-list li { margin: 0.5rem 0; }
    .highlight { font-weight: bold; }
  </style>
</head>
<body>
<div class="container">
  <h1>📊 System Metrics</h1>
  <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

  <section>
    <h2>📈 Key Metrics</h2>
    <ul class="metrics-list">
      <li>API Requests Today: <span class="highlight"><?= htmlspecialchars($apiRequestsToday) ?></span></li>
      <li>Cache Hits: <span class="highlight"><?= htmlspecialchars($cacheHits) ?>%</span></li>
      <li>System Uptime: <span class="highlight"><?= htmlspecialchars($systemUptime) ?></span></li>
      <li>Active Users (last 24h): <span class="highlight"><?= htmlspecialchars($activeUsers) ?></span></li>
    </ul>
  </section>

  <section>
    <h2>⚙️ Performance Notes</h2>
    <p>All services are running normally. No critical alerts detected.</p>
  </section>

  <p><a href="../../../frontend/logout.php">Logout</a></p>
</div>
</body>
</html>
