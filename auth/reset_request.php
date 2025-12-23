<?php
// auth/reset_request.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/log_request.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        log_request(null, 'reset_request', false, 'ERROR', 'Invalid CSRF token');
        header("Location: ../frontend/reset_request.php?error=Invalid request");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        log_request(null, 'reset_request', false, 'ERROR', 'Missing email');
        header("Location: ../frontend/reset_request.php?error=Missing email");
        exit;
    }

    try {
        $pdo = db();

        // ✅ Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            log_request(null, 'reset_request', false, 'ERROR', 'Email not found');
            header("Location: ../frontend/reset_request.php?error=Email not found");
            exit;
        }

        // ✅ Generate token and expiry
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);

        log_request($user['id'], 'reset_request', false, 'SUCCESS', null);

        // ✅ Normally you would email the link to the user
        // For now, redirect with token for testing
        header("Location: ../frontend/reset_confirmation.php?token={$token}&success=Reset link generated");
        exit;

    } catch (Exception $e) {
        log_request(null, 'reset_request', false, 'ERROR', $e->getMessage());
        header("Location: ../frontend/reset_request.php?error=Server error");
        exit;
    }
}
