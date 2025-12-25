<?php

namespace FluentShipment\App\Http\Controllers;

class ShipmentController extends Controller
{
    public function index()
    {
        return [
            'shipments' => [
                [
                    'id' => 1,
                    'order_id' => 1,
                    'order_source' => 'fluent-cart',
                    'tracking_number' => '1234567890',
                    'current_status' => 'delivered',
                    'delivery_address' => '123 Main St',
                    'estimated_delivery' => '2021-01-01',
                    'delivered_at' => '2021-01-01',
                    'customer_id' => 1,
                    'created_at' => '2021-01-01',
                    'updated_at' => '2021-01-01',
                ],
                [
                    'id' => 2,
                    'order_id' => 2,
                    'order_source' => 'woocommerce',
                    'tracking_number' => '1234567890',
                    'current_status' => 'shipped',
                    'delivery_address' => '123 Main St',
                    'estimated_delivery' => '2021-01-01',
                    'delivered_at' => '2021-01-01',
                    'customer_id' => 2,
                    'created_at' => '2021-01-01',
                    'updated_at' => '2021-01-01',
                ],
            ]
        ];
    }
}
