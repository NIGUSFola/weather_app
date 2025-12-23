<?php
// frontend/admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

require_admin(); // enforce admin access
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Multi-Region Weather</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-card { padding: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1.5rem; }
        .success-message { color: green; }
        .error-message { color: red; }
    </style>
</head>
<body>
<main class="page-admin">
    <section class="admin-section">
        <h1>⚙️ Admin Dashboard</h1>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- System Overview -->
        <div class="admin-card">
            <h2>📊 System Overview</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_dashboard.php'; ?>
        </div>

        <!-- Alerts Management -->
        <div class="admin-card">
            <h2>⚠️ Manage Alerts</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_alerts.php'; ?>
        </div>

        <!-- Logs -->
        <div class="admin-card">
            <h2>📝 System Logs</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_logs.php'; ?>
        </div>

        <!-- Metrics -->
        <div class="admin-card">
            <h2>📈 Metrics</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_metrics.php'; ?>
        </div>

        <!-- Configuration -->
        <div class="admin-card">
            <h2>🔧 Configuration</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_config.php'; ?>
        </div>

        <!-- Cache Management -->
        <div class="admin-card">
            <h2>🗄️ Cache Management</h2>
            <?php require_once __DIR__ . '/../backend/ethiopia_service/admin/admin_cache.php'; ?>
        </div>

        <p><a href="logout.php">Logout</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
