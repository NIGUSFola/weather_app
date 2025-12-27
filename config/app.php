<?php
// config/app.php
// Global application-level configuration

$app = [
    'baseUrl'   => 'http://localhost/weather',
    'timezone'  => 'Africa/Addis_Ababa',
    'debug'     => true,
    'session'   => [
        'name'     => 'ethiopia_weather_session',
        'lifetime' => 3600,
    ],
    'security'  => [
        'csrf_token_length' => 32,
        'password_algo'     => PASSWORD_DEFAULT,
    ],
];

// ✅ Ensure timezone is set globally
date_default_timezone_set($app['timezone'] ?? 'UTC');

return $app;
