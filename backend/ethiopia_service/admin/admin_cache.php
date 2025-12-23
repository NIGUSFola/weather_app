<?php
// backend/ethiopia_service/admin/admin_cache.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../helpers/csrf.php';

require_admin();

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Generate CSRF token once per page load
$csrfToken = generate_csrf_token();

// ✅ Handle cache actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: admin_cache.php?error=Invalid+CSRF+token");
        exit;
    }

    $action = $_POST['action'] ?? null;

    try {
        if ($action === 'clear_all') {
            $stmt = db()->prepare("DELETE FROM weather_cache");
            $stmt->execute();
            log_event("Cache cleared by admin", "INFO", [
                'module'=>'admin_cache',
                'admin'=>$_SESSION['user']['id']
            ]);
            header("Location: admin_cache.php?success=All+cache+entries+cleared");
            exit;
        }
        if ($action === 'clear_city') {
            $city = trim($_POST['city'] ?? '');
            $stmt = db()->prepare(
                "DELETE FROM weather_cache WHERE city_id = (SELECT id FROM cities WHERE name = :city)"
            );
            $stmt->execute([':city'=>$city]);
            log_event("Cache cleared for city {$city}", "INFO", [
                'module'=>'admin_cache',
                'admin'=>$_SESSION['user']['id'],
                'city'=>$city
            ]);
            header("Location: admin_cache.php?success=Cache+cleared+for+{$city}");
            exit;
        }
    } catch (Exception $e) {
        header("Location: admin_cache.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ✅ Fetch cache entries
try {
    $stmt = db()->query("SELECT c.name AS city, wc.type, wc.updated_at 
                         FROM weather_cache wc 
                         JOIN cities c ON wc.city_id = c.id 
                         ORDER BY wc.updated_at DESC");
    $cacheEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cacheEntries = [];
    log_event("Failed to fetch cache entries: " . $e->getMessage(), "ERROR", ['module'=>'admin_cache']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Cache Management</title>
    <link rel="stylesheet" href="../../../frontend/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
        .success-message { color: green; }
        .error-message { color: red; }
        .admin-card { margin-top: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄 Cache Management</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-card">
        <h3>Clear All Cache</h3>
        <form method="POST" onsubmit="return confirm('Clear all cache entries?');">
            <input type="hidden" name="action" value="clear_all">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit">Clear All Cache</button>
        </form>
    </div>

    <div class="admin-card">
        <h3>Clear Cache for Specific City</h3>
        <form method="POST" onsubmit="return confirm('Clear cache for this city?');">
            <input type="hidden" name="action" value="clear_city">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>City <input type="text" name="city" required></label>
            <button type="submit">Clear City Cache</button>
        </form>
    </div>

    <div class="admin-card">
        <h3>Existing Cache Entries</h3>
        <table>
            <tr><th>City</th><th>Type</th><th>Last Updated</th></tr>
            <?php if (empty($cacheEntries)): ?>
                <tr><td colspan="3">No cache entries found.</td></tr>
            <?php else: ?>
                <?php foreach ($cacheEntries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['city']) ?></td>
                        <td><?= htmlspecialchars($entry['type']) ?></td>
                        <td><?= htmlspecialchars($entry['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <!-- ✅ Logout form -->
    <form action="../../../auth/logout.php" method="POST" style="margin-top:20px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit">Logout</button>
    </form>
</div>
</body>
</html>
