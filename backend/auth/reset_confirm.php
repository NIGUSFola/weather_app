<?php
// auth/reset_confirmation.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $token       = trim($_POST['token'] ?? '');
        $newPassword = $_POST['password'] ?? '';

        $hasUpper  = preg_match('/[A-Z]/', $newPassword);
        $hasLower  = preg_match('/[a-z]/', $newPassword);
        $hasNumber = preg_match('/[0-9]/', $newPassword);

        if (strlen($newPassword) < 8 || !$hasUpper || !$hasLower || !$hasNumber) {
            $error = "Password must be ≥ 8 chars, include uppercase, lowercase, and numbers";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            try {
                $pdo = db();

                $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
                $stmt->execute([$token]);
                $user = $stmt->fetch();

                if ($user) {
                    $stmt = $pdo->prepare("UPDATE users 
                        SET password_hash = ?, reset_token = NULL, reset_expires = NULL 
                        WHERE id = ?");
                    $stmt->execute([$hash, $user['id']]);

                    header("Location: ../frontend/login.php?success=Password reset successful, please login");
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
?>
