<?php
// auth/reset.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../frontend/reset.php?error=Invalid request");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        header("Location: ../frontend/reset.php?error=Email required");
        exit;
    }

    try {
        $pdo = db();

        // ✅ Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            header("Location: ../frontend/reset.php?error=No account found with that email");
            exit;
        }

        // ✅ Generate a temporary token (for demo purposes)
        $token = bin2hex(random_bytes(16));

        // Store token in DB (optional: create a password_resets table)
        $pdo->prepare("INSERT INTO password_resets (user_id, token, created_at) VALUES (?, ?, NOW())")
            ->execute([$user['id'], $token]);

        // ✅ Normally you’d send an email here
        // For now, just redirect with success message
        header("Location: ../frontend/reset.php?success=Reset link generated (demo token: $token)");
        exit;

    } catch (Exception $e) {
        header("Location: ../frontend/reset.php?error=Server error");
        exit;
    }
}
