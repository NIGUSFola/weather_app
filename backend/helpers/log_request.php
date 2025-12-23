<?php
// backend/helpers/log_request.php
// Centralized request logging helper

require_once __DIR__ . '/../../config/db.php';

/**
 * Log a request event into the database.
 *
 * @param int|null    $userId   The user ID (if available)
 * @param string      $action   The action being performed (e.g., 'login', 'register', 'reset')
 * @param bool        $isAdmin  Whether the request was made by an admin
 * @param string      $status   Status string ('SUCCESS', 'ERROR', etc.)
 * @param string|null $message  Optional error or info message
 */
function log_request(?int $userId, string $action, bool $isAdmin, string $status, ?string $message): void {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO request_logs (user_id, action, is_admin, status, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $isAdmin ? 1 : 0,
            $status,
            $message
        ]);
    } catch (Exception $e) {
        // Fallback logging to PHP error log
        error_log("Failed to log request: " . $e->getMessage());
    }
}
