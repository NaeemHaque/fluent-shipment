<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Services\ShipmentService;

class FluentCartHookHandler
{
    public static function register()
    {
        add_action('fluent_cart/order_paid_done', [__CLASS__, 'handleOrderPaid'], 10, 1);
        add_action('fluent_cart/order_status_changed', [__CLASS__, 'handleOrderStatusChanged'], 10, 1);
    }

    public static function handleOrderPaid($eventData)
    {
        if ( ! apply_filters('fluent_shipment/auto_create_enabled', true)) {
            return;
        }

        if ( ! isset($eventData['order'])) {
            return;
        }

        $order = $eventData['order'];

        if ( ! ShipmentService::isOrderShippable($order)) {
            return;
        }

        $existingShipment = Shipment::where('order_id', $order->id)
                                    ->where('order_source', 'fluent-cart')
                                    ->first();

        if ($existingShipment) {
            return;
        }

        try {
            $shipment = ShipmentService::createFromFluentCartOrder($order);

            if ($shipment) {
                // Optionally trigger a custom action for other plugins
                do_action('fluent_shipment/auto_created', $shipment, $order);
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to auto-create shipment: " . $e->getMessage());
        }
    }

    public static function handleOrderStatusChanged($eventData)
    {
        if ( ! apply_filters('fluent_shipment/auto_sync_enabled', true)) {
            return;
        }

        if ( ! isset($eventData['order']) || ! isset($eventData['newStatus'])) {
            return;
        }

        $order     = $eventData['order'];
        $newStatus = $eventData['newStatus'];

        $shipment = Shipment::where('order_id', $order->id)
                            ->where('order_source', 'fluent-cart')
                            ->first();

        if ( ! $shipment) {
            return;
        }

        $shipmentStatus = ShipmentService::mapFluentCartOrderStatus($newStatus);

        if ($shipmentStatus && $shipmentStatus !== $shipment->current_status) {
            try {
                $shipment->updateStatus($shipmentStatus, [
                    'description' => "Status updated based on FluentCart order status: {$newStatus}",
                    'location'    => 'System Update'
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Failed to update shipment status: " . $e->getMessage());
            }
        }
    }
}
