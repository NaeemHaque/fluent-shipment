<?php if (!$app) return;

$app->addAction('admin_bar_menu', function($wp_admin_bar) use ($app) {
    if (!is_admin() || !$screen = get_current_screen()) return;
    if ($app->env() !== 'dev') return;

    $slug  = $app->config->get('app.slug');
    $page  = 'toplevel_page_' . $slug;

    if ($screen->id === $page) {
        $id   = $slug . '-workbench';
        $name = $app->config->get('app.name');

        $wp_admin_bar->add_node([
            'id'    => $id,
            'title' => '🔧 ' . $name . ' Workbench',
            'href'  => admin_url('admin.php?page='.$slug.'#/dev/workbench'),
        ]);
    }
}, 100);

$app->addAction('init', function() use ($app) {
    include __DIR__ . '/actions/ajax.php';
});

$app->addAction('admin_enqueue_scripts', function() use ($app) {
    wp_enqueue_editor();
});
