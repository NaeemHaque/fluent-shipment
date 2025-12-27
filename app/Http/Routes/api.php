<?php

/**
 * @var $router FluentFramework\Http\Router\Router
 */

use FluentShipment\App\Http\Controllers\ShipmentController;

$router->get('/welcome', 'WelcomeController@index');

$router->prefix('/shipments')->group(function () use ($router) {
    // List shipments
    $router->get('/', [ShipmentController::class, 'index']);
    
    // FluentCart integration
    $router->post('/import/fluent-cart', [ShipmentController::class, 'importFromFluentCart']);
    $router->post('/from-fluent-cart-order/{orderId}', [ShipmentController::class, 'createFromFluentCartOrder']);
    
    // Bulk operations
    $router->post('/bulk/update-status', [ShipmentController::class, 'bulkUpdateStatus']);
    $router->post('/bulk/generate-tracking', [ShipmentController::class, 'generateTrackingNumbers']);
    
    // Individual shipment operations
    $router->get('/{id}', [ShipmentController::class, 'show']);
    $router->put('/{id}/status', [ShipmentController::class, 'updateStatus']);
    $router->put('/{id}/tracking', [ShipmentController::class, 'updateTrackingNumber']);
    $router->delete('/{id}', [ShipmentController::class, 'delete']);
    
    // Tracking events
    $router->get('/{id}/tracking-events', [ShipmentController::class, 'trackingEvents']);
    $router->post('/{id}/tracking-events', [ShipmentController::class, 'addTrackingEvent']);
});
