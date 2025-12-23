<?php
// auth/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../frontend/index.php?error=Invalid logout request");
        exit;
    }

    // ✅ Clear session array
    $_SESSION = [];

    // ✅ Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // ✅ Destroy session
    session_destroy();

    // ✅ Redirect to homepage
    header("Location: ../frontend/index.php?success=You have been logged out");
    exit;
} else {
    header("Location: ../frontend/index.php");
    exit;
}
