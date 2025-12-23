<?php
// frontend/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Clear session
session_unset();
session_destroy();

// ✅ Regenerate session ID for security
session_start();
session_regenerate_id(true);

// ✅ Redirect with success message
header("Location: login.php?success=You have been logged out successfully.");
exit;
