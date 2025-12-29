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

// Register FluentCart integration hooks
$app->addAction('plugins_loaded', function() {
    // Check if FluentCart is active before registering hooks
    if (defined('FLUENT_CART_PLUGIN_VERSION')) {
        \FluentShipment\App\Hooks\Handlers\FluentCartHookHandler::register();
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
