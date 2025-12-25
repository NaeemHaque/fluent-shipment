<?php

return function() {
	$path = __DIR__ . '/../../data/features.json';

    if (!file_exists($path)) {
        wp_send_json_success([
            'features' => [],
            'pagination' => [
                'total' => 0,
                'pages' => 0,
                'current' => 1,
                'limit' => 0
            ]
        ]);
    }

    // Sanitize input
    $limit = intval($_GET['limit'] ?? 10);
    $page  = max(1, intval($_GET['page'] ?? 1));

    // Read data file
    $data = json_decode(file_get_contents($path), true);
    $features = $data['features'] ?? $data ?? [];
    $versions = array_unique(array_column($features, 'version'));

    // Filter by version
    if ($version = sanitize_text_field($_GET['version'] ?? '')) {
        $features = array_filter($features, function($feature) use ($version) {
            return $feature['version'] === $version;
        });
    }

    // Filter by status
    if ($status = sanitize_text_field($_GET['status'] ?? '')) {
        $features = array_filter($features, function($feature) use ($status) {
            return $feature['status'] === $status;
        });
    }

    // Filter by priority
    if ($priority = sanitize_text_field($_GET['priority'] ?? '')) {
        $features = array_filter($features, function($feature) use ($priority) {
            return $feature['priority'] === $priority;
        });
    }

    // Sort by updated_at in descending order
    usort($features, function($a, $b) {
        return $b['updated_at'] > $a['updated_at'];
    });

    // Pagination math
    $total = count($features);
    $pages = $limit > 0 ? ceil($total / $limit) : 1;
    $offset = ($page - 1) * $limit;

    // Slice items for current page
    $items = $limit > 0
        ? array_slice($features, $offset, $limit)
        : $features;

    wp_send_json_success([
        'features' => $items,
        'versions' => array_combine($versions, $versions),
        'pagination' => [
            'total' => $total,
            'pages' => $pages,
            'current' => $page,
            'limit' => $limit
        ]
    ]);
};
