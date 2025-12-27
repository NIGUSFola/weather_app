<?php
// backend/helpers/weather_api.php
// ✅ Centralized weather API helpers (alerts + forecast)

/**
 * Perform a GET request with curl
 */
function curl_get(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT      => 'EthiopiaWeatherApp/1.0 (+http://localhost/weather_app)',
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        error_log('curl_get error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        error_log("curl_get non-200: {$httpCode} for {$url}");
        return null;
    }
    return $res;
}

/**
 * ✅ Resolve city name to lat/lon using OpenWeatherMap Geocoding API
 */
function resolveCityCoordinates(string $cityName, string $apiKey): ?array {
    $geoUrl = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid={$apiKey}";
    $geoRes = curl_get($geoUrl);
    if (!$geoRes) return null;

    $geoData = json_decode($geoRes, true);
    if (!is_array($geoData) || empty($geoData)) return null;

    $lat = $geoData[0]['lat'] ?? null;
    $lon = $geoData[0]['lon'] ?? null;
    if (!$lat || !$lon) return null;

    return ['lat' => $lat, 'lon' => $lon];
}

/**
 * ✅ Fetch alerts for a given city
 */
function getAlertsForCity(string $cityName, string $apiKey, string $lang = 'en'): ?array {
    $coords = resolveCityCoordinates($cityName, $apiKey);
    if (!$coords) return null;

    $url = "https://api.openweathermap.org/data/2.5/onecall?lat={$coords['lat']}&lon={$coords['lon']}&exclude=current,minutely,hourly,daily&appid={$apiKey}&lang={$lang}";
    $res = curl_get($url);
    if (!$res) return null;

    $data = json_decode($res, true);
    if (!isset($data['alerts']) || !is_array($data['alerts'])) return [];

    $alerts = [];
    foreach ($data['alerts'] as $alert) {
        $alerts[] = [
            'title'       => $alert['event'] ?? 'Unknown',
            'description' => $alert['description'] ?? '',
            // OpenWeatherMap alerts don’t always include severity → default to "moderate"
            'severity'    => $alert['severity'] ?? 'moderate',
            'issued_at'   => isset($alert['start']) ? date('Y-m-d H:i', (int)$alert['start']) : date('Y-m-d H:i'),
            'expires_at'  => isset($alert['end']) ? date('Y-m-d H:i', (int)$alert['end']) : null,
            'sender'      => $alert['sender_name'] ?? null
        ];
    }
    return $alerts;
}

/**
 * ✅ Fetch forecast for a given city
 */
function getForecastForCity(string $cityName, string $apiKey, string $lang = 'en', string $units = 'metric'): ?array {
    $coords = resolveCityCoordinates($cityName, $apiKey);
    if (!$coords) return null;

    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$coords['lat']}&lon={$coords['lon']}&appid={$apiKey}&units={$units}&lang={$lang}";
    $res = curl_get($url);
    if (!$res) return null;

    $data = json_decode($res, true);
    if (!isset($data['list']) || !is_array($data['list'])) return [];

    $forecast = [];
    foreach ($data['list'] as $entry) {
        $forecast[] = [
            'date'        => $entry['dt_txt'] ?? '',
            'temperature' => $entry['main']['temp'] ?? null,
            'condition'   => $entry['weather'][0]['description'] ?? '',
            'icon'        => $entry['weather'][0]['icon'] ?? null
        ];
    }
    return $forecast;
}
