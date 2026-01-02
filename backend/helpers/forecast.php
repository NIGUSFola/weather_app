<?php
// backend/helpers/forecast.php

/**
 * Normalize raw forecast data from OpenWeatherMap (5-day/3-hour forecast)
 * into daily summaries for frontend cards.
 *
 * @param array $forecast Raw forecast array from API
 * @return array Normalized forecast (daily)
 */
if (!function_exists('normalize_forecast')) {
    function normalize_forecast(array $forecast): array {
        $normalized = [];

        if (isset($forecast['list']) && is_array($forecast['list'])) {
            $daily = [];

            foreach ($forecast['list'] as $entry) {
                $date = substr($entry['dt_txt'], 0, 10); // YYYY-MM-DD
                $temp = $entry['main']['temp'] ?? null;
                $desc = $entry['weather'][0]['description'] ?? null;
                $icon = $entry['weather'][0]['icon'] ?? null;

                if (!isset($daily[$date])) {
                    $daily[$date] = [
                        'date'      => $date,
                        'min_temp'  => $temp,
                        'max_temp'  => $temp,
                        'condition' => $desc,
                        'icon'      => $icon
                    ];
                } else {
                    // Update min/max
                    if ($temp < $daily[$date]['min_temp']) {
                        $daily[$date]['min_temp'] = $temp;
                    }
                    if ($temp > $daily[$date]['max_temp']) {
                        $daily[$date]['max_temp'] = $temp;
                    }
                    // Overwrite condition/icon with latest (or you could pick midday)
                    $daily[$date]['condition'] = $desc;
                    $daily[$date]['icon']      = $icon;
                }
            }

            // Convert to array sorted by date
            $normalized = array_values($daily);
        }

        return $normalized;
    }
}
