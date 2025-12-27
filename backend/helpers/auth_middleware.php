<?php
// backend/helpers/auth_middleware.php
// Session + role enforcement middleware

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app = require __DIR__ . '/../../config/app.php';

/**
 * Detect if client expects JSON (AJAX or Accept header).
 */
function client_expects_json(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return (stripos($accept, 'application/json') !== false) || !empty($xhr);
}

/**
 * Enforce login and role requirements.
 *
 * @param string|null $requiredRole Role required ('user', 'admin', or null for any login)
 * @param string      $errorMessage Error message to show/return
 */
function enforce_role(?string $requiredRole = null, string $errorMessage = 'Access required'): void {
    $user     = $_SESSION['user'] ?? null;
    $loggedIn = is_array($user) && !empty($user['id']);
    $role     = $user['role'] ?? null;

    // ✅ Allow if logged in and role matches (or no role required)
    if ($loggedIn && ($requiredRole === null || $role === $requiredRole)) {
        return;
    }

    // ✅ JSON response for API clients
    if (client_expects_json()) {
        http_response_code($loggedIn ? 403 : 401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $errorMessage], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Redirect for browser clients
    global $app;
    header('Location: ' . $app['baseUrl'] . '/frontend/login.php?error=' . urlencode($errorMessage));
    exit;
}

/**
 * Require any login (user or admin).
 */
function require_any_login(): void {
    enforce_role(null, 'Login required');
}

/**
 * Require user role.
 */
function require_user(): void {
    enforce_role('user', 'User access required');
}

/**
 * Require admin role.
 */
function require_admin(): void {
    enforce_role('admin', 'Admin access required');
}
