<?php

return function() use ($app) {
	$path = __DIR__ . '/../../data/features.json';
    wp_mkdir_p(dirname($path));
    $request = $app->request;

    $id = $request->get('id', uniqid('feature_id_', true));

    $entry = [
        'id'          => $id,
        'name'        => $request->get('name', ''),
        'priority'    => $request->get('priority'),
        'status'      => $request->get('status'),
        'version'     => $request->get('version'),
        'updated_at'  => current_time('mysql'),
        'description' => $request->getSafe(
            'description', 'wp_kses_post|wp_unslash'
        ),
    ];

    // Load existing data
    if (!file_exists($path)) {
        $data = ['features' => []];
    } else {
        $data = json_decode(
        	file_get_contents($path), true) ?: ['features' => []];
    }

    // Find existing or insert
    $found = false;
    foreach ($data['features'] as &$feature) {
        if ($feature['id'] === $id) {
            // Preserve created_at
            $entry['created_at'] = $feature['created_at'] ?? current_time('mysql');

            // Merge updated fields, preserving created_at
            $feature = array_merge($feature, $entry);
            $found = true;
            break;
        }
    }

    // New feature: add created_at
    if (!$found) {
        $entry['created_at'] = current_time('mysql');
        $data['features'][] = $entry;
    }

    file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT));

    wp_send_json_success([
        'data' => $entry,
        'message' => 'Feature saved successfully!',
    ]);
};
