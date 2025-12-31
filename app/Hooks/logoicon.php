<?php
// Use favicon from resources/images/favicon.png in dev mode
$app->addAction('admin_head', function () use ($app) {
    if (!str_starts_with($app->env(), 'dev')) return;
    
    $screen = get_current_screen();
    $slug = $app->config->get('app.slug');
    if ($screen && $screen->id === "toplevel_page_$slug") {
        $url = plugin_dir_url($app->__pluginfile__) . 'resources/images/favicon.ico';
        $file = plugin_dir_path($app->__pluginfile__) . 'resources/images/favicon.ico';
        if (file_exists($file)) {
            echo '<link rel="icon" href="' . esc_url($url) . '" sizes="32x32" />';
            echo '<link rel="shortcut icon" href="' . esc_url($url) . '" />';
        }
    }
});

// Add style for menu icon (WP left navigation bar)
$app->addAction('admin_head', function() use ($app) {
    $slug = $app->config->get('app.slug');
    $style = "li.toplevel_page_$slug .wp-menu-image";
    $style .= " img { width: 20px; height: 20px; }";
    echo "<style>$style</style>";
});
