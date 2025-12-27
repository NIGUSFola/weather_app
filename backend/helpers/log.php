<?php
// backend/helpers/log.php
// ✅ Centralized file logging

function log_event(string $message, string $level = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] [{$level}] {$message}\n";

    $logFile = __DIR__ . '/../../logs/app.log';
    $dir = dirname($logFile);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        error_log("LOG DIR CREATE FAILED: {$dir}");
        return;
    }

    if (file_put_contents($logFile, $entry, FILE_APPEND) === false) {
        error_log("LOG WRITE FAILED: {$logFile}");
    }
    error_log("{$level}: {$message}");
}
