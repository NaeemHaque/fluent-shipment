<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\App\Services\ShipmentService;

class FluentCartHookHandler 
{
    /**
     * Register FluentCart hooks
     */
    public static function register()
    {
        // Hook into FluentCart order paid event
        add_action('fluent_cart/order_paid_done', [__CLASS__, 'handleOrderPaid'], 10, 1);
        
        // Hook into FluentCart order status changes
        add_action('fluent_cart/order_status_changed', [__CLASS__, 'handleOrderStatusChanged'], 10, 1);
    }

    /**
     * Handle when a FluentCart order is marked as paid
     * 
     * @param array $eventData Contains order, transaction, customer data
     */
    public static function handleOrderPaid($eventData)
    {
        // Check if auto-creation is enabled
        if (!apply_filters('fluent_shipment/auto_create_enabled', true)) {
            return;
        }

        if (!isset($eventData['order'])) {
            return;
        }

        $order = $eventData['order'];
        
        // Check if this order is eligible for shipment
        if (!ShipmentService::isOrderShippable($order)) {
            return;
        }

        // Check if shipment already exists for this order
        $existingShipment = \FluentShipment\App\Models\Shipment::where('order_id', $order->id)
            ->where('order_source', 'fluent-cart')
            ->first();

        if ($existingShipment) {
            return; // Shipment already exists
        }

        // Create shipment automatically
        try {
            $shipment = ShipmentService::createFromFluentCartOrder($order);
            
            if ($shipment) {
                // Log the auto-creation
                error_log("FluentShipment: Auto-created shipment #{$shipment->id} for FluentCart order #{$order->id}");
                
                // Optionally trigger a custom action for other plugins
                do_action('fluent_shipment/auto_created', $shipment, $order);
            }
        } catch (\Exception $e) {
            error_log("FluentShipment: Failed to auto-create shipment for order #{$order->id}: " . $e->getMessage());
        }
    }

    /**
     * Handle FluentCart order status changes
     * 
     * @param array $eventData Contains order and status information
     */
    public static function handleOrderStatusChanged($eventData)
    {
        // Check if auto-sync is enabled
        if (!apply_filters('fluent_shipment/auto_sync_enabled', true)) {
            return;
        }

        if (!isset($eventData['order']) || !isset($eventData['newStatus'])) {
            return;
        }

        $order = $eventData['order'];
        $newStatus = $eventData['newStatus'];
        
        // Find existing shipment for this order
        $shipment = \FluentShipment\App\Models\Shipment::where('order_id', $order->id)
            ->where('order_source', 'fluent-cart')
            ->first();

        if (!$shipment) {
            return; // No shipment exists yet
        }

        // Map FluentCart order status to shipment status if needed
        $shipmentStatus = ShipmentService::mapFluentCartOrderStatus($newStatus);
        
        if ($shipmentStatus && $shipmentStatus !== $shipment->current_status) {
            try {
                $shipment->updateStatus($shipmentStatus, [
                    'description' => "Status updated based on FluentCart order status: {$newStatus}",
                    'location' => 'System Update'
                ]);
                
                error_log("FluentShipment: Updated shipment #{$shipment->id} status to {$shipmentStatus} based on order status change");
            } catch (\Exception $e) {
                error_log("FluentShipment: Failed to update shipment status for order #{$order->id}: " . $e->getMessage());
            }
        }
    }
}