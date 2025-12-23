<?php
// backend/helpers/validator.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize a string input (trim, strip tags).
 */
function sanitize_string(string $value): string {
    return trim(strip_tags($value));
}

/**
 * Validate city name (letters, spaces, hyphens).
 */
function validate_city(string $city): bool {
    return (bool)preg_match('/^[\p{L}\s\-]+$/u', $city);
}

/**
 * Validate integer input within range.
 */
function validate_int($value, int $min = null, int $max = null): bool {
    if (!is_numeric($value)) return false;
    $intVal = (int)$value;
    if ($min !== null && $intVal < $min) return false;
    if ($max !== null && $intVal > $max) return false;
    return true;
}

/**
 * Validate API key format (basic alphanumeric check).
 */
function validate_api_key(string $key): bool {
    return (bool)preg_match('/^[A-Za-z0-9]{20,64}$/', $key);
}
