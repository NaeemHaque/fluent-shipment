<?php

return function() {
	$path = realpath(__DIR__ . '/../../data/overview.json');

    if (!file_exists($path)) {
        wp_send_json_error(['message' => 'No overview data found.']);
    }

    $decoded = json_decode(file_get_contents($path), true);

    if (!is_array($decoded)) {
        wp_send_json_error(['message' => 'Invalid JSON data.']);
    }

    wp_send_json_success([
        'overview' => $decoded,
    ]);
};
