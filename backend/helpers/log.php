<?php
// backend/helpers/log.php
// Centralized event logging helper

/**
 * Log a system event.
 *
 * @param string $message The message to log
 * @param string $level   Log level ('INFO', 'WARN', 'ERROR')
 */
function log_event(string $message, string $level = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    // ✅ Write to a dedicated log file
    $logFile = __DIR__ . '/../../logs/system.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0775, true);
    }

    file_put_contents($logFile, $entry, FILE_APPEND);

    // ✅ Also send to PHP error log for redundancy
    error_log("{$level}: {$message}");
}
