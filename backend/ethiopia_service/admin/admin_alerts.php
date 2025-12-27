<?php
// backend/ethiopia_service/admin/admin_alerts.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/csrf.php';

require_admin();

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Generate CSRF token once per page load
$csrfToken = generate_csrf_token();

// ✅ Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: admin_alerts.php?error=Invalid+CSRF+token");
        exit;
    }

    $action      = $_POST['action'] ?? null;
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $severity    = $_POST['severity'] ?? 'low';
    $id          = $_POST['id'] ?? null;

    try {
        $pdo = db();

        if ($action === 'add' && $title && $description) {
            $stmt = $pdo->prepare(
                "INSERT INTO alerts (title, description, severity, created_by, issued_at) 
                 VALUES (:title, :description, :severity, :created_by, NOW())"
            );
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':severity' => $severity,
                ':created_by' => $_SESSION['user']['id'] ?? null
            ]);
            header("Location: admin_alerts.php?success=Alert+added+successfully");
            exit;
        }

        if ($action === 'edit' && $id) {
            $stmt = $pdo->prepare(
                "UPDATE alerts 
                 SET title=:title, description=:description, severity=:severity 
                 WHERE id=:id"
            );
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':severity' => $severity,
                ':id' => $id
            ]);
            header("Location: admin_alerts.php?success=Alert+updated+successfully");
            exit;
        }

        if ($action === 'delete' && $id) {
            $stmt = $pdo->prepare("DELETE FROM alerts WHERE id=:id");
            $stmt->execute([':id' => $id]);
            header("Location: admin_alerts.php?success=Alert+deleted+successfully");
            exit;
        }
    } catch (Exception $e) {
        header("Location: admin_alerts.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ✅ Fetch existing alerts
try {
    $stmt = db()->query(
        "SELECT id, title, description, severity, issued_at 
         FROM alerts 
         ORDER BY issued_at DESC"
    );
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $alerts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Alerts</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
        .success-message { color: green; }
        .error-message { color: red; }
        .admin-card { margin-bottom: 2rem; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚠️ Manage Severe Weather Alerts</h1>

    <!-- ✅ Include fixed navigation -->
    <?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Add Alert -->
    <div class="admin-card">
        <h3>Add New Alert</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>Title <input type="text" name="title" required></label><br>
            <label>Description <textarea name="description" required></textarea></label><br>
            <label>Severity 
                <select name="severity" required>
                    <option value="low">Low</option>
                    <option value="moderate">Moderate</option>
                    <option value="high">High</option>
                </select>
            </label><br>
            <button type="submit">Add Alert</button>
        </form>
    </div>

    <!-- Existing Alerts -->
    <div class="admin-card">
        <h3>Existing Alerts</h3>
        <table>
            <tr><th>Title</th><th>Description</th><th>Severity</th><th>Issued At</th><th>Actions</th></tr>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="5">No alerts available.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['title']) ?></td>
                        <td><?= htmlspecialchars($alert['description']) ?></td>
                        <td><?= htmlspecialchars($alert['severity']) ?></td>
                        <td><?= htmlspecialchars($alert['issued_at']) ?></td>
                        <td>
                            <!-- Edit Form -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($alert['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars($alert['title']) ?>" required>
                                <input type="text" name="description" value="<?= htmlspecialchars($alert['description']) ?>" required>
                                <select name="severity">
                                    <option value="low" <?= $alert['severity']=='low'?'selected':'' ?>>Low</option>
                                    <option value="moderate" <?= $alert['severity']=='moderate'?'selected':'' ?>>Moderate</option>
                                    <option value="high" <?= $alert['severity']=='high'?'selected':'' ?>>High</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                            <!-- Delete Form -->
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this alert?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($alert['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
