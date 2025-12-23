<?php
// backend/config/app.php
// Global application-level configuration

return [
    // Base URL for frontend routing
    'baseUrl'   => 'http://localhost/weather_app',

    // Default timezone
    'timezone'  => 'Africa/Addis_Ababa',

    // Debug mode (true = verbose errors, false = production safe)
    'debug'     => true,

    // Session settings
    'session'   => [
        'name'     => 'ethiopia_weather_session',
        'lifetime' => 3600, // seconds
    ],

    // Security settings
    'security'  => [
        'csrf_token_length' => 32,
        'password_algo'     => PASSWORD_DEFAULT,
    ],
];
