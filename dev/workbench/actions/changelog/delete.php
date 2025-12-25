<?php

return function() use ($app) {
    $path = __DIR__ . '/../../data/log.json';
    wp_mkdir_p(dirname($path));

    $request = $app->request;
    $id = sanitize_text_field($request->get('id'));

    if (!$id) {
        wp_send_json_error(['message' => 'Missing log ID']);
    }

    if (file_exists($path)) {
        $logs = json_decode(
        	file_get_contents($path), true
        ) ?: ['logs' => []];
    } else {
        wp_send_json_error(['message' => 'No logs found']);
    }

    $originalCount = count($logs['logs']);

    $logs['logs'] = array_values(
    	array_filter($logs['logs'],
    	function($log) use ($id) {
        	return empty($log['id']) || $log['id'] !== $id;
    	})
    );

    if (count($logs['logs']) === $originalCount) {
        wp_send_json_error(['message' => 'Log not found']);
    }

    file_put_contents(
        $path,
        wp_json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    wp_send_json_success(['message' => 'Log deleted successfully']);
};
