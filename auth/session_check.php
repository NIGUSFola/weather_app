<?php
// auth/session_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ If user is not logged in, redirect to login with error
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php?error=You must be logged in to access this page");
    exit;
}

// ✅ Optional: enforce role for admin-only pages
// if (($_SESSION['role'] ?? '') !== 'admin') {
//     header("Location: ../frontend/dashboard.php?error=Admin access required");
//     exit;
// }
