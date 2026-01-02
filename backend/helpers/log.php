<?php
// backend/helpers/log.php
// Centralized logging helper for Ethiopia Weather app

require_once __DIR__ . '/../../config/db.php';

/**
 * Write a log entry into the system_logs table.
 *
 * @param string $message   The log message
 * @param string $level     Log level: INFO, WARN, ERROR
 * @param array  $context   Optional context array (e.g. ['module'=>'favorite_alerts','user_id'=>123])
 */
function log_event(string $message, string $level = 'INFO', array $context = []): void {
    try {
        $pdo = db();

        // Normalize level
        $level = strtoupper($level);
        if (!in_array($level, ['INFO','WARN','ERROR'])) {
            $level = 'INFO';
        }

        // Convert context to JSON
        $contextJson = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare("
            INSERT INTO system_logs (created_at, level, message, context)
            VALUES (NOW(), :level, :message, :context)
        ");
        $stmt->execute([
            ':level'   => $level,
            ':message' => $message,
            ':context' => $contextJson
        ]);
    } catch (Exception $e) {
        // Fallback: write to PHP error log if DB insert fails
        error_log("Logging failed: ".$e->getMessage()." | Original message: ".$message);
    }
}
