<?php

return function() use ($app) {
	$path = __DIR__ . '/../../data/overview.json';
    wp_mkdir_p(dirname($path));
    $request = $app->request;
	
    $description = $request->getSafe('description', 'sanitize_text_field');
    $launch_date = $request->getSafe('launch_date', 'sanitize_text_field');
    $status  = $request->getSafe('status', 'sanitize_text_field');
    $problem     = $request->getSafe('problem', 'wp_kses_post');
    $solution    = $request->getSafe('solution', 'wp_kses_post');

    // Read existing JSON if any
    $existing = [];
    if (file_exists($path)) {
        $contents = file_get_contents($path);
        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $existing = $decoded;
        }
    }

    // If this is a first-time creation, generate id and created_at
    if (empty($existing)) {
        $entry = [
            'id'          => uniqid('prd_', true),
            'created_at'  => current_time('mysql'),
            'description' => $description,
            'launch_date' => $launch_date,
            'status'  => $status,
            'problem'     => $problem,
            'solution'    => $solution,
            'updated_at'  => current_time('mysql'),
        ];
    } else {
        // Existing entry: merge updated fields but preserve id and created_at
        $entry = array_merge($existing, [
            'description' => $description,
            'launch_date' => $launch_date,
            'status'  => $status,
            'problem'     => $problem,
            'solution'    => $solution,
            'updated_at'  => current_time('mysql'),
        ]);
    }

    // Encode and save as JSON
    $json = wp_json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $result = file_put_contents($path, $json);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to write file.']);
    }

    wp_send_json_success([
        'message' => 'Overview saved successfully.',
        'path'    => $path,
        'data'    => $entry,
    ]);
};
