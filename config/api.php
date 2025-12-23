<?php
// backend/config/api.php
// Centralized API configuration for Ethiopia Weather App

$apiConfig = [
    // 🔑 OpenWeather API key (replace with your real key)
    'openweathermap' => '23216f21b0836e2f87baf74559caed54',

    // Optional: rate limit for logged-in users (requests per hour)
    'rateLimit' => 100,

    // Optional: default units for OpenWeather API (metric, imperial, standard)
    'units' => 'metric',

    // Optional: default language for API responses
    'lang' => 'en'
];
