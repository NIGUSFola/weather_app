<?php
// backend/helpers/alerts.php

/**
 * Normalize raw alerts data from OpenWeatherMap or demo fallback
 *
 * @param array $alerts Raw alerts array from API
 * @return array Normalized alerts
 */
if (!function_exists('normalize_alerts')) {
    function normalize_alerts(array $alerts): array {
        $normalized = [];

        foreach ($alerts as $alert) {
            $normalized[] = [
                'event'       => $alert['event'] ?? 'General Weather Alert',
                'description' => $alert['description'] ?? 'No description provided',
                'start'       => isset($alert['start'])
                    ? date('Y-m-d H:i:s', is_numeric($alert['start']) ? $alert['start'] : strtotime($alert['start']))
                    : null,
                'end'         => isset($alert['end'])
                    ? date('Y-m-d H:i:s', is_numeric($alert['end']) ? $alert['end'] : strtotime($alert['end']))
                    : null,
                'severity'    => strtolower($alert['severity'] ?? 'moderate'),
                'sender_name' => $alert['sender_name'] ?? 'Unknown Source'
            ];
        }

        return $normalized;
    }
}

/**
 * Provide a demo fallback alert if none exist
 *
 * @param string $city City name for demo alert
 * @return array Demo alert array
 */
if (!function_exists('demo_alert')) {
    function demo_alert(string $city): array {
        return [[
            'event'       => "Demo Alert for {$city}",
            'description' => "Simulated severe weather in {$city}",
            'start'       => date('Y-m-d H:i:s'),
            'end'         => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'severity'    => 'moderate',
            'sender_name' => 'Demo Service'
        ]];
    }
}
