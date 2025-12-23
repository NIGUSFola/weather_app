<?php
// auth/login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../frontend/login.php?error=Invalid request");
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        header("Location: ../frontend/login.php?error=Missing required fields");
        exit;
    }

    try {
        $pdo = db();

        // ✅ Fetch user by email
        $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ Verify password against stored hash
        if (!$user || !password_verify($password, $user['password_hash'])) {
            header("Location: ../frontend/login.php?error=Invalid email or password");
            exit;
        }

        // ✅ Set session values
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];

        // ✅ Redirect by role
        switch ($user['role']) {
            case 'admin':
                header("Location: ../frontend/dashboard.php?success=Welcome back, Admin!");
                break;
            case 'user':
                header("Location: ../frontend/user_dashboard.php?success=Welcome back!");
                break;
            default:
                header("Location: ../frontend/index.php?success=Welcome back!");
                break;
        }
        exit;

    } catch (Exception $e) {
        // ✅ Fail gracefully
        header("Location: ../frontend/login.php?error=Server error");
        exit;
    }
} else {
    // ✅ If accessed directly, redirect to login form
    header("Location: ../frontend/login.php");
    exit;
}
