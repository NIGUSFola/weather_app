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

// ✅ CSRF token for logout
$logoutToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Ethiopia Weather</title>
   <link rel="stylesheet" href="/weather/frontend/partials/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 220px;
            background: #f8f9fa;
            border-right: 1px solid #ddd;
            padding: 1rem;
        }
        .sidebar h2 {
            margin-bottom: 1rem;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar li {
            margin: 0.5rem 0;
        }
        .sidebar a {
            text-decoration: none;
            color: #0366d6;
            font-weight: bold;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .dashboard-content {
            flex: 1;
            padding: 2rem;
        }
        .admin-card {
            padding: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .success-message { color: green; }
        .error-message { color: red; }
    </style>
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
        <?php if (!empty($flash)): ?>
          <div class="success-message"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
