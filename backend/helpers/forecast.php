<?php
// backend/helpers/forecast.php

/**
 * Normalize raw forecast data from OpenWeatherMap into a consistent format
 *
 * @param array $forecast Raw forecast array from API
 * @return array Normalized forecast
 */
if (!function_exists('normalize_forecast')) {
    function normalize_forecast(array $forecast): array {
        $normalized = [];

        if (!isset($forecast['list']) || !is_array($forecast['list'])) {
            return $normalized;
        }

        foreach ($forecast['list'] as $entry) {
            $normalized[] = [
                'time'        => $entry['dt_txt'] ?? null,
                'temperature' => $entry['main']['temp'] ?? null,
                'humidity'    => $entry['main']['humidity'] ?? null,
                'condition'   => $entry['weather'][0]['description'] ?? null,
                'icon'        => $entry['weather'][0]['icon'] ?? null,
                'wind_speed'  => $entry['wind']['speed'] ?? null
            ];
        }

        return $normalized;
    }
}
