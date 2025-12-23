<?php
// backend/ethiopia_service/admin/admin_api.php

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


$csrfToken = generate_csrf_token();
// ✅ Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: admin_api.php?error=Invalid+CSRF+token");
        exit;
    }

    $action = $_POST['action'] ?? null;
    $key    = $_POST['key'] ?? null;

    try {
        if ($action === 'delete' && $key) {
            $stmt = db()->prepare("DELETE FROM api_keys WHERE api_key = :api_key");
            $stmt->execute([':api_key' => $key]);
            log_event("Admin deleted API key", "INFO", [
                'module' => 'admin_api',
                'admin'  => $_SESSION['user']['id'],
                'api_key'=> $key
            ]);
            header("Location: admin_api.php?success=API+key+deleted");
            exit;
        }
    } catch (Exception $e) {
        header("Location: admin_api.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ✅ Fetch all keys
try {
    $stmt = db()->query("SELECT ak.id, ak.key_name, ak.api_key, ak.created_at, u.email 
                         FROM api_keys ak 
                         JOIN users u ON ak.user_id = u.id 
                         ORDER BY ak.created_at DESC");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $keys = [];
    log_event("Failed to fetch API keys: " . $e->getMessage(), "ERROR", ['module'=>'admin_api']);
}

// ✅ Generate CSRF token
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage API Keys</title>
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
    <h1>🔑 Manage API Keys</h1>
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-card">
        <h3>All User API Keys</h3>
        <table>
            <tr><th>User</th><th>Key Name</th><th>API Key</th><th>Created At</th><th>Actions</th></tr>
            <?php if (empty($keys)): ?>
                <tr><td colspan="5">No API keys found.</td></tr>
            <?php else: ?>
                <?php foreach ($keys as $key): ?>
                    <tr>
                        <td><?= htmlspecialchars($key['email']) ?></td>
                        <td><?= htmlspecialchars($key['key_name']) ?></td>
                        <td><?= htmlspecialchars($key['api_key']) ?></td>
                        <td><?= htmlspecialchars($key['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this API key?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="key" value="<?= htmlspecialchars($key['api_key']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
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
