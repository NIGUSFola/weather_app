<?php
// backend/helpers/csrf.php
// Centralized CSRF protection helper

// ✅ Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in session.
 * If one already exists, reuse it to avoid overwriting between forms.
 * Call this once per page load and reuse the token in all forms.
 */
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the session value.
 * By default, this is single-use: it invalidates the token after checking.
 *
 * @param string $token The token submitted by the form
 * @param bool $invalidate Whether to unset the token after validation (default true)
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token(string $token, bool $invalidate = true): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    $isValid = hash_equals($_SESSION['csrf_token'], $token);

    // ✅ Invalidate token after use to prevent replay attacks
    if ($invalidate && $isValid) {
        unset($_SESSION['csrf_token']);
    }

    return $isValid;
}

/**
 * Verify CSRF token (alias for validate_csrf_token).
 * This allows consistency across admin and user files.
 * Uses multi-use mode (does not unset immediately).
 *
 * @param string $token The token submitted by the form
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token(string $token): bool {
    return validate_csrf_token($token, false);
}
