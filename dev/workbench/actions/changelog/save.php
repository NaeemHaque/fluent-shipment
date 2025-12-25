<?php

return function() use ($app) {
    $path = __DIR__ . '/../../data/log.json';
    wp_mkdir_p(dirname($path));

    $request = $app->request;

    $id = sanitize_text_field($request->get('id'));
    $name = sanitize_text_field($request->get('name'));
    $content = wp_kses_post($request->get('content'));

    if (!$name) {
        wp_send_json_error(['message' => 'Missing log name']);
    }

    if (file_exists($path)) {
        $logs = json_decode(file_get_contents($path), true) ?: ['logs' => []];
    } else {
        $logs = ['logs' => []];
    }

    $now = current_time('mysql');
    $found = false;

    if ($id) {
        foreach ($logs['logs'] as &$log) {
            if (!empty($log['id']) && $log['id'] === $id) {
                $log['name'] = $name;
                $log['content'] = $content;
                $log['updated_at'] = $now;
                if (empty($log['created_at'])) {
                    $log['created_at'] = $now;
                }
                $found = true;
                break;
            }
        }
        unset($log);
    }

    // If not found, create a new one
    if (!$found) {
        $newId = $id ?: uniqid('log_', true);

        $logs['logs'][] = [
            'id'         => $newId,
            'name'       => $name,
            'content'    => $content,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    usort($logs['logs'], function($a, $b) {
        return strtotime(
        	$b['created_at'] ?? 'now'
        ) <=> strtotime($a['created_at'] ?? 'now');
    });

    file_put_contents(
        $path,
        wp_json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    wp_send_json_success([
    	'log' => $logs['logs'][0],
    	'message' => 'Log saved successfully.'
    ]);
};
