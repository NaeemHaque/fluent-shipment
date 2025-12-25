<?php

use FluentShipment\Framework\Foundation\Application;
use FluentShipment\App\Hooks\Handlers\ActivationHandler;
use FluentShipment\App\Hooks\Handlers\DeactivationHandler;

return function($file) {
    try {
        $app = new Application($file);

        register_activation_hook($file, function() use ($app) {
            ($app->make(ActivationHandler::class))->handle();
        });

        register_deactivation_hook($file, function() use ($app) {
            ($app->make(DeactivationHandler::class))->handle();
        });

        add_action('plugins_loaded', function() use ($app) {
            do_action('fluentshipment_loaded', $app);
        });

        return $app;

    } catch (Throwable $e) {
        try {
            $message = sprintf(
                "[%s] %s in %s:%d\nStack trace:\n%s\n",
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            
            error_log($message);

            $config = require(__DIR__.'/../config/app.php');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                do_action("{$config['slug']}_exception", $e);
            }
        } catch (Throwable $inner) {
            error_log("Exception in exception handler: " . $inner->getMessage());
        }
    }
};
