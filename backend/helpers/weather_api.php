<?php
// backend/helpers/weather_api.php
// ✅ Centralized weather API helpers (alerts + forecast)

require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/log.php';

/**
 * Simple CURL GET wrapper with logging
 */
function curl_get(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    if ($res === false) {
        log_event("cURL failed for URL: $url", "ERROR", ['module' => 'weather_api']);
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return $res;
}

/**
 * ✅ Fetch alerts for a given city (via geocoding)
 */
function getAlertsForCity(string $cityName, string $apiKey): array {
    $units = $GLOBALS['apiConfig']['units'] ?? 'metric';
    $lang  = $GLOBALS['apiConfig']['lang'] ?? 'en';

    // Step 1: Resolve city to lat/lon
    $geoUrl = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid=" . $apiKey;
    $geoRes = curl_get($geoUrl);
    if (!$geoRes) return [];
    $geoData = json_decode($geoRes, true);
    if (!is_array($geoData) || empty($geoData)) return [];

    $lat = $geoData[0]['lat'] ?? null;
    $lon = $geoData[0]['lon'] ?? null;
    if (!$lat || !$lon) return [];

    // Step 2: Fetch alerts via One Call API
    $url = "https://api.openweathermap.org/data/2.5/onecall?lat={$lat}&lon={$lon}&exclude=current,minutely,hourly,daily&units={$units}&lang={$lang}&appid=" . $apiKey;
    $res = curl_get($url);
    if (!$res) return [];

    $data = json_decode($res, true);
    if (!isset($data['alerts'])) return [];

    // Step 3: Normalize alerts
    $alerts = [];
    foreach ($data['alerts'] as $alert) {
        $alerts[] = [
            'event'       => $alert['event'] ?? 'Unknown',
            'description' => $alert['description'] ?? '',
            'start'       => isset($alert['start']) ? date('Y-m-d H:i', $alert['start']) : '',
            'end'         => isset($alert['end']) ? date('Y-m-d H:i', $alert['end']) : '',
            'sender'      => $alert['sender_name'] ?? 'N/A',
            'severity'    => $alert['severity'] ?? 'moderate'
        ];
    }
    return $alerts;
}

/**
 * ✅ Fetch forecast for a given city (via geocoding)
 */
function getForecastForCity(string $cityName, string $apiKey): array {
    $units = $GLOBALS['apiConfig']['units'] ?? 'metric';
    $lang  = $GLOBALS['apiConfig']['lang'] ?? 'en';

    // Step 1: Resolve city to lat/lon
    $geoUrl = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid=" . $apiKey;
    $geoRes = curl_get($geoUrl);
    if (!$geoRes) return [];
    $geoData = json_decode($geoRes, true);
    if (!is_array($geoData) || empty($geoData)) return [];

    $lat = $geoData[0]['lat'] ?? null;
    $lon = $geoData[0]['lon'] ?? null;
    if (!$lat || !$lon) return [];

    // Step 2: Fetch forecast (5-day / 3-hour intervals)
    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units={$units}&lang={$lang}&appid=" . $apiKey;
    $res = curl_get($url);
    if (!$res) return [];

    $data = json_decode($res, true);
    if (!isset($data['list'])) return [];

    // Step 3: Normalize forecast entries
    $forecast = [];
    foreach ($data['list'] as $entry) {
        $forecast[] = [
            'datetime' => $entry['dt_txt'] ?? '',
            'temp'     => $entry['main']['temp'] ?? '',
            'weather'  => $entry['weather'][0]['description'] ?? ''
        ];
    }
    return $forecast;
}

/**
 * ✅ Fetch forecast directly by coordinates
 */
function getForecastByCoords(float $lat, float $lon, string $apiKey): array {
    $units = $GLOBALS['apiConfig']['units'] ?? 'metric';
    $lang  = $GLOBALS['apiConfig']['lang'] ?? 'en';

    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units={$units}&lang={$lang}&appid=" . $apiKey;
    $res = curl_get($url);
    if (!$res) return [];

    $data = json_decode($res, true);
    if (!isset($data['list'])) return [];

    $forecast = [];
    foreach ($data['list'] as $entry) {
        $forecast[] = [
            'datetime' => $entry['dt_txt'] ?? '',
            'temp'     => $entry['main']['temp'] ?? '',
            'weather'  => $entry['weather'][0]['description'] ?? ''
        ];
    }
    return $forecast;
}

/**
 * ✅ Fetch alerts directly by coordinates
 */
function getAlertsByCoords(float $lat, float $lon, string $apiKey): array {
    $units = $GLOBALS['apiConfig']['units'] ?? 'metric';
    $lang  = $GLOBALS['apiConfig']['lang'] ?? 'en';

    $url = "https://api.openweathermap.org/data/2.5/onecall?lat={$lat}&lon={$lon}&exclude=current,minutely,hourly,daily&units={$units}&lang={$lang}&appid=" . $apiKey;
    $res = curl_get($url);
    if (!$res) return [];

    $data = json_decode($res, true);
    if (!isset($data['alerts'])) return [];

    $alerts = [];
    foreach ($data['alerts'] as $alert) {
        $alerts[] = [
            'event'       => $alert['event'] ?? 'Unknown',
            'description' => $alert['description'] ?? '',
            'start'       => isset($alert['start']) ? date('Y-m-d H:i', $alert['start']) : '',
            'end'         => isset($alert['end']) ? date('Y-m-d H:i', $alert['end']) : '',
            'sender'      => $alert['sender_name'] ?? 'N/A',
            'severity'    => $alert['severity'] ?? 'moderate'
        ];
    }
    return $alerts;
}
