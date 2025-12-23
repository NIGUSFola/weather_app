<?php
// frontend/partials/admin_nav.php
?>
<nav class="admin-nav" >
    <ul>
        <li><a href="../../../backend/ethiopia_service/admin/admin_dashboard.php">📊 View All Users</a></li>
        <li><a href="../../../backend/ethiopia_service/admin/admin_alerts.php">⚠️ Manage Alerts</a></li>
        <li><a href="../../../backend/ethiopia_service/admin/admin_dashboard.php#logs">📝 System Logs</a></li>
        <li>
            <form action="../../../auth/logout.php" method="POST" >
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                <button type="submit">🚪 Logout</button>
            </form>
        </li>
    </ul>
</nav>
