<?php

return function() use ($app) {
    $slug = $app->config->get('app.slug');

    $response = wp_remote_get(
        home_url("/wp-json/{$slug}/v2/{$slug}/__endpoints"),
        [
            'headers' => ['X-From-CLI' => md5($slug)]
        ]
    );

    $code = wp_remote_retrieve_response_code($response);

    $routes = json_decode(wp_remote_retrieve_body($response), true);

    wp_send_json_success([
        'routes' => $routes
    ]);
};
