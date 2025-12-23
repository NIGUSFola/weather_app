<?php
// frontend/partials/admin_nav.php
require_once __DIR__ . '/../../backend/helpers/csrf.php';
?>
<nav class="admin-nav">
  <ul>
    <li><a href="/weather_app/backend/ethiopia_service/admin/admin_dashboard.php">📊 View All Users</a></li>
    <li><a href="/weather_app/backend/ethiopia_service/admin/admin_alerts.php">⚠️ Manage Alerts</a></li>
    <li><a href="/weather_app/backend/ethiopia_service/admin/admin_logs.php">📝 System Logs</a></li>
  
  </ul>
</nav>
