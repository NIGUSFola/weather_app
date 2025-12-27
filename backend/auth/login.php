<?php
// backend/auth/login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';      // Correct path to config
require_once __DIR__ . '/../helpers/csrf.php';      // Correct path to helpers

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../../frontend/login.php?error=Invalid request");
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        header("Location: ../../frontend/login.php?error=Missing required fields");
        exit;
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            header("Location: ../../frontend/login.php?error=Invalid email or password");
            exit;
        }

        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];

        $redirect = "../../frontend/index.php?success=Welcome back!";
        if ($user['role'] === 'admin') {
            $redirect = "../../frontend/dashboard.php?success=Welcome back, Admin!";
        } elseif ($user['role'] === 'user') {
            $redirect = "../../frontend/user_dashboard.php?success=Welcome back!";
        }

        header("Location: $redirect");
        exit;

    } catch (Exception $e) {
        header("Location: ../../frontend/login.php?error=Server error");
        exit;
    }
} else {
    header("Location: ../../frontend/login.php");
    exit;
}
