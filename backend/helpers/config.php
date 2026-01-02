<?php
// backend/helpers/config.php
// Helper for reading configuration values from system_config table

require_once __DIR__ . '/../../config/db.php';

/**
 * Get a configuration value from system_config.
 *
 * @param string $key     The config_key to look up.
 * @param mixed  $default Default value if not found.
 * @return string|mixed
 */
function getConfig(string $key, $default = null) {
    try {
        $stmt = db()->prepare("SELECT config_value FROM system_config WHERE config_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        if ($val !== false && $val !== null) {
            return $val;
        }
    } catch (Exception $e) {
        // Optionally log the error
        // log_event("Config lookup failed for {$key}: ".$e->getMessage(), "ERROR", ['module'=>'config']);
    }
    return $default;
}

/**
 * Convenience wrappers
 */
function getApiKey(): string {
    return getConfig('openweathermap', '');
}

function getCacheDuration(): int {
    return (int) getConfig('cacheDuration', 30);
}

function getRateLimit(): int {
    return (int) getConfig('rateLimit', 100);
}
