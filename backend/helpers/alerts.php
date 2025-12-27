<?php
// backend/helpers/alerts.php

/**
 * Normalize raw alerts data from OpenWeatherMap into a consistent format
 *
 * @param array $alerts Raw alerts array from API
 * @return array Normalized alerts
 */
if (!function_exists('normalize_alerts')) {
    function normalize_alerts(array $alerts): array {
        $normalized = [];

        foreach ($alerts as $alert) {
            $normalized[] = [
                'event'       => $alert['event'] ?? 'Unknown',
                'description' => $alert['description'] ?? '',
                'start'       => isset($alert['start'])
                    ? date('Y-m-d H:i:s', is_numeric($alert['start']) ? $alert['start'] : strtotime($alert['start']))
                    : null,
                'end'         => isset($alert['end'])
                    ? date('Y-m-d H:i:s', is_numeric($alert['end']) ? $alert['end'] : strtotime($alert['end']))
                    : null,
                'severity'    => strtolower($alert['severity'] ?? 'moderate')
            ];
        }

        return $normalized;
    }
}
