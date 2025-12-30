<?php

/**
 * @var $router FluentFramework\Http\Router\Router
 */

use FluentShipment\App\Http\Controllers\DashboardController;
use FluentShipment\App\Http\Controllers\ShipmentController;
use FluentShipment\App\Http\Controllers\RiderController;


$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/migrate', [DashboardController::class, 'runMigrations']);

$router->prefix('/shipments')->group(function () use ($router) {
    // List shipments
    $router->get('/', [ShipmentController::class, 'index']);
    
    // FluentCart integration
    $router->post('/import/fluent-cart', [ShipmentController::class, 'importFromFluentCart']);
    $router->post('/from-fluent-cart-order/{orderId}', [ShipmentController::class, 'createFromFluentCartOrder']);
    
    // Bulk operations
    $router->post('/bulk/update-status', [ShipmentController::class, 'bulkUpdateStatus']);
    
    // Individual shipment operations
    $router->get('/{id}', [ShipmentController::class, 'show']);
    $router->put('/{id}/status', [ShipmentController::class, 'updateStatus']);
    $router->put('/{id}/tracking', [ShipmentController::class, 'updateTrackingNumber']);
    $router->delete('/{id}', [ShipmentController::class, 'delete']);
    
    // Tracking events
    $router->get('/{id}/tracking-events', [ShipmentController::class, 'trackingEvents']);
    $router->post('/{id}/tracking-events', [ShipmentController::class, 'addTrackingEvent']);
});

$router->prefix('/riders')->group(function () use ($router) {
    // List riders
    $router->get('/', [RiderController::class, 'index']);
    
    // Create new rider
    $router->post('/', [RiderController::class, 'store']);
    
    // Get rider statistics
    $router->get('/stats', [RiderController::class, 'stats']);
    
    // Get active riders for assignments
    $router->get('/active', [RiderController::class, 'getActiveRiders']);
    
    // Search riders
    $router->get('/search', [RiderController::class, 'search']);
    
    // Bulk operations
    $router->post('/bulk/update-status', [RiderController::class, 'bulkUpdateStatus']);
    
    // Individual rider operations
    $router->get('/{id}', [RiderController::class, 'show']);
    $router->put('/{id}', [RiderController::class, 'update']);
    $router->put('/{id}/status', [RiderController::class, 'updateStatus']);
    $router->put('/{id}/rating', [RiderController::class, 'updateRating']);
    $router->delete('/{id}', [RiderController::class, 'delete']);
});
