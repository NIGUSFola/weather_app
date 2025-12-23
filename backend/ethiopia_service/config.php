<?php
// backend/ethiopia_service/config.php

// ✅ Pure config loader (no session, no JSON output)
// This file is required by service endpoints to load settings.

return [
    'openweathermap' => getenv('OPENWEATHERMAP_KEY') ?: '',
    'cacheDuration'  => 30,   // minutes
    'rateLimit'      => 100   // requests per minute
];
