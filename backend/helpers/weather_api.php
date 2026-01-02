<?php
// backend/helpers/weather_api.php
// ✅ Centralized weather API helpers (alerts + forecast)

// Hardcoded coordinates for Ethiopian cities
$cityCoords = [
    'Shashamane'  => ['lat' => 7.20, 'lon' => 38.60],
    'Hawassa'     => ['lat' => 7.05, 'lon' => 38.50],
    'Bahir Dar'   => ['lat' => 11.59, 'lon' => 37.39],
    'Addis Ababa' => ['lat' => 9.03, 'lon' => 38.74],
];

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
 * ✅ Resolve city name to lat/lon using hardcoded table first, then OpenWeather Geocoding API
 */
function resolveCityCoordinates(string $cityName, string $apiKey): ?array {
    global $cityCoords;

    if (isset($cityCoords[$cityName])) {
        return $cityCoords[$cityName];
    }

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
 * ✅ Fetch alerts for a given city (OpenWeather One Call API)
 */
function getAlertsForCity(string $cityName, string $apiKey, string $lang = 'en'): ?array {
    $coords = resolveCityCoordinates($cityName, $apiKey);
    if (!$coords) return null;

    $url = "https://api.openweathermap.org/data/3.0/onecall?lat={$coords['lat']}&lon={$coords['lon']}&appid={$apiKey}&lang={$lang}&units=metric";
    $res = curl_get($url);
    if (!$res) return null;

    $data = json_decode($res, true);
    if (!isset($data['alerts']) || !is_array($data['alerts'])) {
        return [];
    }

    $alerts = [];
    foreach ($data['alerts'] as $alert) {
        // Normalize severity
        $severity = 'moderate';
        if (!empty($alert['tags']) && is_array($alert['tags'])) {
            $tag = strtolower($alert['tags'][0]);
            if (in_array($tag, ['moderate','severe','extreme'])) {
                $severity = $tag;
            }
        }

        $alerts[] = [
            'event'       => $alert['event'] ?? 'Unknown',
            'description' => $alert['description'] ?? '',
            'severity'    => $severity,
            'start'       => $alert['start'] ?? time(),
            'end'         => $alert['end'] ?? null,
            'sender_name' => $alert['sender_name'] ?? null
        ];
    }
    return $alerts;
}

/**
 * ✅ Fetch forecast for a given city (grouped daily summaries)
 */
function getForecastForCity(string $cityName, string $apiKey, string $lang = 'en', string $units = 'metric'): ?array {
    $coords = resolveCityCoordinates($cityName, $apiKey);
    if (!$coords) return null;

    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$coords['lat']}&lon={$coords['lon']}&appid={$apiKey}&units={$units}&lang={$lang}";
    $res = curl_get($url);
    if (!$res) return null;

    $data = json_decode($res, true);
    if (!isset($data['list']) || !is_array($data['list']) || count($data['list']) === 0) {
        return [];
    }

    // ✅ Group by day (min/max temp, most frequent condition, icon)
    $daily = [];
    foreach ($data['list'] as $entry) {
        $date = substr($entry['dt_txt'], 0, 10); // YYYY-MM-DD
        $tempMin = $entry['main']['temp_min'] ?? $entry['main']['temp'];
        $tempMax = $entry['main']['temp_max'] ?? $entry['main']['temp'];
        $condition = $entry['weather'][0]['description'] ?? '';
        $icon = $entry['weather'][0]['icon'] ?? null;

        if (!isset($daily[$date])) {
            $daily[$date] = [
                'date'      => $date,
                'min_temp'  => $tempMin,
                'max_temp'  => $tempMax,
                'conditions'=> [$condition],
                'icons'     => [$icon]
            ];
        } else {
            $daily[$date]['min_temp'] = min($daily[$date]['min_temp'], $tempMin);
            $daily[$date]['max_temp'] = max($daily[$date]['max_temp'], $tempMax);
            $daily[$date]['conditions'][] = $condition;
            $daily[$date]['icons'][] = $icon;
        }
    }

    // Pick most frequent condition/icon per day
    $result = [];
    foreach ($daily as $day) {
        $conditionCounts = array_count_values($day['conditions']);
        arsort($conditionCounts);
        $topCondition = array_key_first($conditionCounts);

        $iconCounts = array_count_values($day['icons']);
        arsort($iconCounts);
        $topIcon = array_key_first($iconCounts);

        $result[] = [
            'date'      => $day['date'],
            'min_temp'  => $day['min_temp'],
            'max_temp'  => $day['max_temp'],
            'condition' => $topCondition,
            'icon'      => $topIcon
        ];
    }

    return $result;
}
