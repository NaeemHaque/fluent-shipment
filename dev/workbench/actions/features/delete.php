<?php

return function() {
	$path = __DIR__ . '/../../data/features.json';

    // Validate file existence
    if (!file_exists($path)) {
        wp_send_json_error(['message' => 'Features file not found.']);
    }

    // Sanitize input
    $id = sanitize_text_field($_POST['id'] ?? '');

    if (empty($id)) {
        wp_send_json_error(['message' => 'Missing feature ID.']);
    }

    // Read and decode existing features
    $data = json_decode(file_get_contents($path), true);
    $features = $data['features'] ?? $data ?? [];

    // Filter out the feature with the matching ID
    $new_features = array_filter($features, function($feature) use ($id) {
        return isset($feature['id']) && $feature['id'] !== $id;
    });

    // If no change occurred, item not found
    if (count($features) === count($new_features)) {
        wp_send_json_error(['message' => 'Feature not found.']);
    }

    // Save updated list
    $saved = file_put_contents(
        $path,
        wp_json_encode(['features' => array_values($new_features)], JSON_PRETTY_PRINT)
    );

    if (!$saved) {
        wp_send_json_error(['message' => 'Failed to save updated features.']);
    }

    wp_send_json_success([
        'message' => 'Feature deleted successfully.',
        'id' => $id
    ]);
};
