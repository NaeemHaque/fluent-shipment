<?php

return function() use ($app) {
    $path = __DIR__ . '/../../data/log.json';

    if (!file_exists($path)) {
        $logs = ['logs' => []];
    } else {
        $logs = json_decode(file_get_contents($path), true) ?: ['logs' => []];
    }

    usort($logs['logs'], function($a, $b) {
        return strtotime(
        	$b['created_at'] ?? 'now'
        ) <=> strtotime($a['created_at'] ?? 'now');
    });

    wp_send_json_success(['logs' => $logs['logs']]);
};
