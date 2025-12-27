<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(): string {
    $app = require __DIR__ . '/../../config/app.php';
    $length = $app['security']['csrf_token_length'] ?? 32;
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes($length));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(string $token, bool $invalidate = true): bool {
    if (!isset($_SESSION['csrf_token'])) return false;
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    if ($invalidate && $isValid) unset($_SESSION['csrf_token']);
    return $isValid;
}

function verify_csrf_token(string $token): bool {
    return validate_csrf_token($token, false);
}
