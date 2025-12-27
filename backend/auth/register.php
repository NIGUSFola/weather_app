<?php
// backend/auth/register.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../../frontend/register.php?error=Invalid request");
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($email === '' || $password === '' || $confirm === '') {
        header("Location: ../../frontend/register.php?error=Missing required fields");
        exit;
    }

    if ($password !== $confirm) {
        header("Location: ../../frontend/register.php?error=Passwords do not match");
        exit;
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: ../../frontend/register.php?error=Email already registered");
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, 'user', NOW())");
        $stmt->execute([$email, $hash]);

        header("Location: ../../frontend/login.php?success=Account created successfully, please login");
        exit;

    } catch (Exception $e) {
        header("Location: ../../frontend/register.php?error=Server error");
        exit;
    }
} else {
    header("Location: ../../frontend/register.php");
    exit;
}
