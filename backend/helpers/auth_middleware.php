<?php
// backend/helpers/auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Detect if the client expects JSON (API/AJAX) or HTML (browser).
 */
function client_expects_json(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return (stripos($accept, 'application/json') !== false) || !empty($xhr);
}

/**
 * Enforce role-based access.
 *
 * @param string|null $requiredRole   Role required ('user', 'admin', or null for any login)
 * @param string      $errorMessage   Message to show on failure
 */
function enforce_role(?string $requiredRole = null, string $errorMessage = 'Access required'): void {
    $user = $_SESSION['user'] ?? null;

    $loggedIn = is_array($user) && !empty($user['id']);
    $role     = $user['role'] ?? null;

    // ✅ Access granted if logged in and role matches (or no role required)
    if ($loggedIn && ($requiredRole === null || $role === $requiredRole)) {
        return;
    }

    // ✅ API/AJAX clients get JSON error
    if (client_expects_json()) {
        http_response_code($loggedIn ? 403 : 401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $errorMessage]);
        exit;
    }

    // ✅ Browser clients get redirect
    header('Location: /weather_app/frontend/login.php?error=' . urlencode($errorMessage));
    exit;
}

/**
 * Require any logged-in user
 */
function require_any_login(): void {
    enforce_role(null, 'Login required');
}

/**
 * Require user role
 */
function require_user(): void {
    enforce_role('user', 'User access required');
}

/**
 * Require admin role
 */
function require_admin(): void {
    enforce_role('admin', 'Admin access required');
}
