<?php

include_once __DIR__ . '/logoicon.php';

/**
 * All registered action's handlers should be in app\Hooks\Handlers,
 * addAction is similar to add_action and addCustomAction is just a
 * wrapper over add_action which will add a prefix to the hook name
 * using the plugin slug to make it unique in all wordpress plugins,
 * ex: $app->addCustomAction('foo', ['FooHandler', 'handleFoo']) is
 * equivalent to add_action('slug-foo', ['FooHandler', 'handleFoo']).
 */

/**
 * @var $app FluentShipment\Framework\Foundation\Application
 */

$app->addAction('admin_menu', 'AdminMenuHandler');

$app->addCustomAction('exception', 'ExceptionHandler');

$app->addAction('plugins_loaded', function() {
    if (defined('FLUENTCART_VERSION')) {
        \FluentShipment\App\Hooks\Handlers\FluentCartHookHandler::register();
        \FluentShipment\App\Hooks\Handlers\FluentCartOrderTrackingHandler::register();
    }
});

// Register public shortcode
$app->addAction('init', function() {
    $trackingShortcode = new \FluentShipment\App\Shortcodes\TrackingShortcode();
    $trackingShortcode->register();
});

/**
 * Enable this line if you want to use custom post types
 */

// $app->addAction('init', 'CPTHandler@registerPostTypes');

add_action('admin_init', function () {

    if (!is_admin()) {
        return;
    }

    $screen = get_current_screen();

    if ($screen && strpos($screen->id, 'fluentshipment') !== false) {
        return;
    }

    // Remove all admin notices
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
});
