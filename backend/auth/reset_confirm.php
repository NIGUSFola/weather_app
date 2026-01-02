<?php
// backend/auth/reset_confirmation.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $token       = trim($_POST['token'] ?? '');
        $newPassword = $_POST['password'] ?? '';

        // ✅ Password strength check
        $hasUpper  = preg_match('/[A-Z]/', $newPassword);
        $hasLower  = preg_match('/[a-z]/', $newPassword);
        $hasNumber = preg_match('/[0-9]/', $newPassword);

        if (strlen($newPassword) < 8 || !$hasUpper || !$hasLower || !$hasNumber) {
            $error = "Password must be ≥ 8 chars, include uppercase, lowercase, and numbers";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            try {
                $pdo = db();

                // ✅ Look up token in password_resets table
                $stmt = $pdo->prepare("
                    SELECT pr.user_id, u.email 
                    FROM password_resets pr
                    JOIN users u ON pr.user_id = u.id
                    WHERE pr.token = ? 
                      AND (pr.expires_at IS NULL OR pr.expires_at > NOW())
                    ORDER BY pr.created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$token]);
                $user = $stmt->fetch();

                if ($user) {
                    // ✅ Update user password
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$hash, $user['user_id']]);

                    // ✅ Clean up used token
                    $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

                    header("Location: ../../frontend/login.php?success=Password reset successful, please login");
                    exit;
                } else {
                    $error = "Invalid or expired reset token.";
                }
            } catch (Exception $e) {
                $error = "Reset confirmation failed.";
            }
        }
    }
}

// If there was an error, redirect back with message
if ($error !== '') {
    header("Location: ../../frontend/reset_confirmation.php?error=" . urlencode($error));
    exit;
}
?>
