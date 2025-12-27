<?php
// backend/auth/session_check.php
// Simple session guard for pages that require login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ If user is not logged in, redirect to login with error
$user = $_SESSION['user'] ?? null;
if (!is_array($user) || empty($user['id'])) {
    header("Location: ../frontend/login.php?error=" . urlencode("You must be logged in to access this page"));
    exit;
}

// ✅ Optional: enforce role for admin-only pages
// if (($user['role'] ?? '') !== 'admin') {
//     header("Location: ../frontend/dashboard.php?error=" . urlencode("Admin access required"));
//     exit;
// }
