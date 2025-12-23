<?php
// auth/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: ../frontend/register.php?error=Invalid request");
        exit;
    }

    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ✅ Basic checks
    if ($email === '' || $password === '' || $confirm_password === '') {
        header("Location: ../frontend/register.php?error=Missing required fields");
        exit;
    }

    if ($password !== $confirm_password) {
        header("Location: ../frontend/register.php?error=Passwords do not match");
        exit;
    }

    // ✅ Strong password rules
    $hasUpper  = preg_match('/[A-Z]/', $password);
    $hasLower  = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);

    if (strlen($password) < 8 || !$hasUpper || !$hasLower || !$hasNumber) {
        header("Location: ../frontend/register.php?error=Weak password");
        exit;
    }

    try {
        $pdo = db();

        // ✅ Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: ../frontend/register.php?error=Email already registered");
            exit;
        }

        // ✅ Insert new user with hashed password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, 'user', NOW())"
        );
        $stmt->execute([$email, $hashedPassword]);

        // ✅ Redirect to login with success message
        header("Location: ../frontend/login.php?success=Registration successful, please login");
        exit;

    } catch (Exception $e) {
        // Optional debug during development:
        // echo "<pre>Registration failed:\n" . $e->getMessage() . "</pre>"; exit;

        header("Location: ../frontend/register.php?error=Server error");
        exit;
    }
}
