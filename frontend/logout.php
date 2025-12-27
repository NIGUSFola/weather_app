<?php
// frontend/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_unset();
session_destroy();

// Regenerate session ID for security
session_start();
session_regenerate_id(true);

// Redirect to login with success message
header("Location: /weather/frontend/login.php?success=" . urlencode("You have been logged out successfully."));
exit;
