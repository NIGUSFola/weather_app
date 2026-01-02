<?php
// config/app.php
// Global application-level configuration for Multi-Region Weather Aggregator

$app = [
    'baseUrl'   => 'http://localhost/weather',
    'timezone'  => 'Africa/Addis_Ababa',
    'debug'     => true,

    // ✅ Session settings
    'session'   => [
        'name'     => 'ethiopia_weather_session',
        'lifetime' => 3600, // 1 hour
    ],

    // ✅ Security settings
    'security'  => [
        'csrf_token_length' => 32,
        'password_algo'     => PASSWORD_DEFAULT,
    ],

    // ✅ Rate limiting (per user/session)
    'rateLimit' => [
        'requests_per_minute' => 100,
        'burst_limit'         => 200,
    ],

    // ✅ Cache TTLs (seconds)
    'cache'     => [
        'forecast_ttl' => 600,   // 10 minutes
        'alerts_ttl'   => 900,   // 15 minutes
    ],

    // ✅ Service registry (distributed region endpoints)
    'services'  => [
'Oromia'      => 'http://localhost/weather/backend/ethiopia_service/regions/oromia',
'Amhara'      => 'http://localhost/weather/backend/ethiopia_service/regions/amhara',
'South'       => 'http://localhost/weather/backend/ethiopia_service/regions/south',
'Addis Ababa' => 'http://localhost/weather/backend/ethiopia_service/regions/addis_ababa',

    ],
];

// ✅ Ensure timezone is set globally
date_default_timezone_set($app['timezone'] ?? 'UTC');

return $app;
