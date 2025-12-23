<?php
// auth/monitor.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_once __DIR__ . '/../backend/helpers/log.php';

// Monitor session activity
$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;

if ($userId) {
    log_event("Session monitor: User {$userId} active with role {$role}", "INFO");
} else {
    log_event("Session monitor: No active user", "WARN");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session Monitor - Ethiopia Weather</title>
    <link rel="stylesheet" href="../frontend/style.css">
</head>
<body>
<div class="container">
    <h1>🖥 Session Monitor</h1>
    <?php if ($userId): ?>
        <p>User ID: <?= htmlspecialchars($userId) ?></p>
        <p>Role: <?= htmlspecialchars($role) ?></p>
    <?php else: ?>
        <p>No active session.</p>
    <?php endif; ?>
</div>
</body>
</html>
