<?php
// backend/bootstrap.php
// Load app config, session, timezone, error handling

$app = require __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    if (!empty($app['session']['name'])) {
        session_name($app['session']['name']);
    }
    session_start();
    if (!empty($app['timezone'])) {
        date_default_timezone_set($app['timezone']);
    }
}

// Error visibility based on config
if (!empty($app['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}
