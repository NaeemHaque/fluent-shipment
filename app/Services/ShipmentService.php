<?php

namespace FluentShipment\App\Services;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\ShipmentTrackingEvent;

class ShipmentService
{
    public static function generateTrackingNumber($type = 'auto')
    {
        do {
            $trackingNumber = static::buildTrackingNumber($type);
            $exists         = Shipment::where('tracking_number', $trackingNumber)->exists();
        } while ($exists);

        return $trackingNumber;
    }

    private static function buildTrackingNumber($type)
    {
        $prefix    = static::getTrackingPrefix($type);
        $timestamp = date('Ymd');

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length     = 8;
        $random     = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $prefix . $timestamp . $random;
    }


    private static function getTrackingPrefix($type)
    {
        $typePrefix = $type === 'manual' ? 'M' : 'A';

        return 'FS' . $typePrefix;
    }

    public static function mapFluentCartShippingStatus($shippingStatus)
    {
        $statusMap = [
            'unshipped'   => Shipment::STATUS_PENDING,
            'shipped'     => Shipment::STATUS_SHIPPED,
            'delivered'   => Shipment::STATUS_DELIVERED,
            'unshippable' => Shipment::STATUS_CANCELLED,
        ];

        return $statusMap[$shippingStatus] ?? Shipment::STATUS_PENDING;
    }

    public static function mapFluentCartOrderStatus($orderStatus)
    {
        $statusMap = [
            'pending'    => Shipment::STATUS_PENDING,
            'processing' => Shipment::STATUS_PROCESSING,
            'completed'  => Shipment::STATUS_SHIPPED, // Completed orders can be marked as shipped
            'cancelled'  => Shipment::STATUS_CANCELLED,
            'refunded'   => Shipment::STATUS_CANCELLED,
            'failed'     => Shipment::STATUS_FAILED,
        ];

        return $statusMap[$orderStatus] ?? null;
    }

    public static function mapToFluentCartShippingStatus($shipmentStatus)
    {
        $statusMap = [
            Shipment::STATUS_PENDING          => 'unshipped',
            Shipment::STATUS_PROCESSING       => 'unshipped',
            Shipment::STATUS_SHIPPED          => 'shipped',
            Shipment::STATUS_IN_TRANSIT       => 'shipped',
            Shipment::STATUS_OUT_FOR_DELIVERY => 'shipped',
            Shipment::STATUS_DELIVERED        => 'delivered',
            Shipment::STATUS_FAILED           => 'unshippable',
            Shipment::STATUS_CANCELLED        => 'unshippable',
            Shipment::STATUS_RETURNED         => 'unshippable',
            Shipment::STATUS_EXCEPTION        => 'unshippable',
        ];

        return $statusMap[$shipmentStatus] ?? 'unshipped';
    }

    public static function formatAddressArray($address)
    {
        if ( ! $address) {
            return [];
        }

        return [
            'name'      => $address->name ?? '',
            'address_1' => $address->address_1 ?? '',
            'address_2' => $address->address_2 ?? '',
            'city'      => $address->city ?? '',
            'state'     => $address->state ?? '',
            'postcode'  => $address->postcode ?? '',
            'country'   => $address->country ?? '',
            'email'     => $address->email ?? '',
        ];
    }

    public static function formatAddressString($address, $includeName = true)
    {
        if (empty($address)) {
            return '';
        }

        $parts = [];

        if ($includeName && ! empty($address['name'])) {
            $parts[] = $address['name'];
        }

        if ( ! empty($address['address_1'])) {
            $parts[] = $address['address_1'];
        }

        if ( ! empty($address['address_2'])) {
            $parts[] = $address['address_2'];
        }

        $cityStateZip = array_filter([
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postcode'] ?? '',
        ]);

        if ( ! empty($cityStateZip)) {
            $parts[] = implode(', ', $cityStateZip);
        }

        if ( ! empty($address['country'])) {
            $parts[] = $address['country'];
        }

        return implode(', ', array_filter($parts));
    }

    public static function calculateEstimatedDelivery($origin = [], $destination = [])
    {
        $days = 5; // Default delivery days

        // Add weekend buffer
        $estimatedDate = new \DateTime();
        $estimatedDate->add(new \DateInterval('P' . $days . 'D'));

        // Skip weekends for business delivery
        while ($estimatedDate->format('N') >= 6) {
            $estimatedDate->add(new \DateInterval('P1D'));
        }

        return $estimatedDate->format('Y-m-d');
    }

    public static function createFromFluentCartOrder($order, $options = [])
    {
        if ( ! static::isOrderShippable($order)) {
            return false;
        }

        $existing = Shipment::where('order_id', $order->id)
                            ->where('order_source', Shipment::SOURCE_FLUENT_CART)
                            ->first();

        if ($existing) {
            return false;
        }

        $shippingAddress = $order->shipping_address;
        if ( ! $shippingAddress) {
            return false;
        }

        $shipmentData = [
            'order_id'             => $order->id,
            'order_source'         => Shipment::SOURCE_FLUENT_CART,
            'order_hash'           => $order->uuid ?? null,
            'tracking_number'      => static::generateTrackingNumber(),
            'current_status'       => static::mapFluentCartShippingStatus($order->shipping_status ?? 'unshipped'),
            'shipping_address'     => static::formatAddressArray($shippingAddress),
            'delivery_address'     => static::formatAddressArray($shippingAddress),
            'estimated_delivery'   => static::calculateEstimatedDelivery(),
            'customer_id'          => $order->customer_id,
            'customer_email'       => $order->customer->email ?? $shippingAddress->email ?? null,
            'shipping_cost'        => $order->shipping_total ?? 0,
            'currency'             => $order->currency ?? 'USD',
            'package_info'         => static::extractPackageInfo($order),
            'weight_total'         => static::calculateTotalWeight($order),
            'special_instructions' => $order->note ?? null,
        ];

        $shipment = Shipment::create($shipmentData);

        if ($shipment) {
            $shipment->createTrackingEvent($shipment->current_status, [
                'description' => 'Shipment created from order #' . ($order->invoice_no ?? $order->id),
                'location'    => 'Fulfillment Center',
            ]);
        }

        return $shipment;
    }

    public static function isOrderShippable($order)
    {
        if ($order->fulfillment_type !== 'physical') {
            return false;
        }

        if ( ! in_array($order->payment_status, ['paid', 'partially_paid'])) {
            return false;
        }

        if ( ! in_array($order->status, ['processing', 'completed'])) {
            return false;
        }

        if ( ! $order->shipping_address) {
            return false;
        }

        return true;
    }

    public static function extractPackageInfo($order)
    {
        $items = [];

        if ($order->order_items) {
            foreach ($order->order_items as $item) {
                $items[] = [
                    'product_id'   => $item->post_id,
                    'variation_id' => $item->object_id,
                    'name'         => $item->title . ' - ' . $item->post_title,
                    'quantity'     => $item->quantity,
                    'weight'       => $item->weight ?? 0,
                ];
            }
        }

        return [
            'items'          => $items,
            'total_items'    => count($items),
            'total_quantity' => array_sum(array_column($items, 'quantity')),
        ];
    }

    public static function calculateTotalWeight($order)
    {
        $totalWeight = 0;

        if ($order->order_items) {
            foreach ($order->order_items as $item) {
                $weight      = $item->weight ?? 0;
                $quantity    = $item->quantity ?? 1;
                $totalWeight += ($weight * $quantity);
            }
        }

        return $totalWeight;
    }

    public static function syncToFluentCartOrder(Shipment $shipment)
    {
        if ($shipment->order_source !== Shipment::SOURCE_FLUENT_CART) {
            return false;
        }

        if ( ! class_exists('\FluentCart\App\Models\Order')) {
            return false;
        }

        $orderClass = '\FluentCart\App\Models\Order';
        $order      = $orderClass::find($shipment->order_id);

        if ( ! $order) {
            return false;
        }

        $fluentCartStatus = static::mapToFluentCartShippingStatus($shipment->current_status);

        $order->shipping_status = $fluentCartStatus;

        return $order->save();
    }

    public static function bulkCreateFromFluentCartOrders($orderIds, $options = [])
    {
        $results = [
            'created' => [],
            'skipped' => [],
            'errors'  => [],
        ];

        if ( ! class_exists('\FluentCart\App\Models\Order')) {
            $results['errors'][] = 'FluentCart plugin not found';

            return $results;
        }

        $orderClass = '\FluentCart\App\Models\Order';
        $orders     = $orderClass::with(['shipping_address', 'customer', 'order_items'])
                                 ->whereIn('id', $orderIds)
                                 ->get();

        foreach ($orders as $order) {
            $shipment = static::createFromFluentCartOrder($order, $options);

            if ($shipment) {
                $results['created'][] = [
                    'order_id'        => $order->id,
                    'shipment_id'     => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                ];
            } else {
                $reason = 'Unknown error';

                if ( ! static::isOrderShippable($order)) {
                    $reason = 'Order not shippable';
                }

                $existing = Shipment::where('order_id', $order->id)
                                    ->where('order_source', Shipment::SOURCE_FLUENT_CART)
                                    ->first();

                if ($existing) {
                    $reason = 'Shipment already exists';
                }

                $results['skipped'][] = [
                    'order_id' => $order->id,
                    'reason'   => $reason,
                ];
            }
        }

        return $results;
    }
}
