<?php
// backend/ethiopia_service/logger.php

require_once __DIR__ . '/../helpers/log.php';

/**
 * Service-specific logging wrapper.
 * Delegates to global log_event() but adds module context.
 */
function service_log(string $message, string $level = 'INFO', array $context = []): void {
    log_event($message, $level, array_merge(['module' => 'ethiopia_service'], $context));
}
