<?php

/**
 * @var $router FluentFramework\Http\Router\Router
 */

use FluentShipment\App\Http\Controllers\ShipmentController;

$router->get('/welcome', 'WelcomeController@index');

$router->prefix('/shipments')->group(function () use ($router) {
    $router->get('/', [ShipmentController::class, 'index']);
});
