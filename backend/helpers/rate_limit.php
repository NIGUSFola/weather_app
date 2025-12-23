<?php
// backend/helpers/rate_limit.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/log.php';

/**
 * Enforce per-user rate limit.
 * Logs request and blocks if limit exceeded.
 */
function enforce_rate_limit(int $limitPerMinute = 100): void {
    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) return;

    try {
        // Count requests in last minute
        $stmt = db()->prepare("SELECT COUNT(*) FROM api_requests 
                               WHERE user_id = ? 
                               AND requested_at > (NOW() - INTERVAL 1 MINUTE)");
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= $limitPerMinute) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Rate limit exceeded. Please wait before retrying.']);
            log_event("Rate limit exceeded", "WARN", ['module'=>'rate_limit','user_id'=>$userId]);
            exit;
        }

        // Log request
        $stmt = db()->prepare("INSERT INTO api_requests (user_id, endpoint) VALUES (?, ?)");
        $stmt->execute([$userId, $_SERVER['SCRIPT_NAME']]);
    } catch (Exception $e) {
        log_event("Rate limit check failed: " . $e->getMessage(), "ERROR", ['module'=>'rate_limit','user_id'=>$userId]);
        // Fail open: allow request if rate limit check fails
    }
}
