<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Rider;
use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\ShipmentTrackingEvent;
use FluentShipment\App\Services\ShipmentService;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Http\Request\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->getSafe('per_page', 'intval', 15), 100); // Cap at 100
        $page    = $request->getSafe('page', 'intval', 1);

        $status         = $request->getSafe('status', 'sanitize_text_field');
        $orderSource    = $request->getSafe('order_source', 'sanitize_text_field');
        $trackingNumber = $request->getSafe('tracking_number', 'sanitize_text_field');
        $customerEmail  = $request->getSafe('customer_email', 'sanitize_email');
        $dateFrom       = $request->getSafe('date_from', 'sanitize_text_field');
        $dateTo         = $request->getSafe('date_to', 'sanitize_text_field');
        $search         = $request->getSafe('search', 'sanitize_text_field');

        $with        = $request->getSafe('with', 'fluentShipmentSanitizeArray', []);
        $allowedWith = ['trackingEvents', 'latestTrackingEvent', 'rider'];
        $with        = array_intersect((array)$with, $allowedWith);

        if ( ! in_array('rider', $with)) {
            $with[] = 'rider';
        }

        $query = Shipment::query();

        if ($status) {
            if (is_array($status)) {
                $query->whereIn('current_status', $status);
            } else {
                $query->where('current_status', $status);
            }
        }

        if ($orderSource) {
            $query->where('order_source', $orderSource);
        }

        if ($trackingNumber) {
            $query->where('tracking_number', 'LIKE', "%{$trackingNumber}%");
        }

        if ($customerEmail) {
            $query->where('customer_email', 'LIKE', "%{$customerEmail}%");
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%")
                  ->orWhere('order_id', 'LIKE', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(delivery_address, '$.name') LIKE ?", ["%{$search}%"]);
            });
        }

        if ( ! empty($with)) {
            $query->with($with);
        }

        $shipments = $query->orderBy('created_at', 'DESC')
                           ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success'         => true,
            'data'            => $shipments->toArray(),
            'filters_applied' => compact(
                'status',
                'orderSource',
                'trackingNumber',
                'customerEmail',
                'dateFrom',
                'dateTo',
                'search'
            ),
        ];
    }

    public function importFromFluentCart(Request $request)
    {
        if ( ! class_exists('\FluentCart\App\Models\Order')) {
            return [
                'success' => false,
                'message' => 'FluentCart plugin is not active',
            ];
        }

        $request->validate([
            'filters' => 'array',
            'options' => 'array',
        ]);

        $filters = $request->getSafe('filters', 'fluentShipmentSanitizeArray', []);
        $options = $request->getSafe('options', 'fluentShipmentSanitizeArray', []);

        // Get fluent-cart status constants
        $statusClass = '\FluentCart\App\Helpers\Status';
        $orderClass  = '\FluentCart\App\Models\Order';

        // Build base query for eligible orders
        $query = $orderClass::with(['shipping_address', 'customer', 'order_items'])
                            ->where('fulfillment_type', $statusClass::FULFILLMENT_TYPE_PHYSICAL)
                            ->whereHas('shipping_address')
                            ->where('shipping_status', '!=', $statusClass::SHIPPING_UNSHIPPABLE);

        if ( ! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if ( ! empty($filters['order_status'])) {
            $query->where('status', $filters['order_status']);
        }

        if ( ! empty($filters['shipping_status'])) {
            $query->where('shipping_status', $filters['shipping_status']);
        }

        if ( ! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ( ! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $orders = $query->get();

        $orderIds                 = $orders->pluck('id')->toArray();
        $existingShipmentOrderIds = Shipment::where('order_source', Shipment::SOURCE_FLUENT_CART)
                                            ->whereIn('order_id', $orderIds)
                                            ->pluck('order_id')
                                            ->toArray();

        $newOrderIds = array_diff($orderIds, $existingShipmentOrderIds);

        $results = ['created' => [], 'skipped' => []];

        foreach ($orders as $order) {
            if (in_array($order->id, $newOrderIds)) {
                $shipment = ShipmentService::createFromFluentCartOrder($order);
                if ($shipment) {
                    $results['created'][] = $shipment->id;
                } else {
                    $results['skipped'][] = $order->id;
                }
            }
        }

        return [
            'success'             => true,
            'total_orders_found'  => $orders->count(),
            'existing_shipments'  => count($existingShipmentOrderIds),
            'eligible_for_import' => count($newOrderIds),
            'results'             => $results,
        ];
    }

    public function createFromFluentCartOrder(Request $request, $orderId)
    {
        if ( ! class_exists('\FluentCart\App\Models\Order')) {
            return [
                'success' => false,
                'message' => 'FluentCart plugin is not active',
            ];
        }

        $request->validate([
            'carrier'            => 'string|in:' . implode(',', Shipment::getCarriers()),
            'service'            => 'string|max:100',
            'estimated_delivery' => 'date|after:today',
        ]);

        $orderClass = '\FluentCart\App\Models\Order';
        $order      = $orderClass::with(['shipping_address', 'customer', 'order_items'])->find($orderId);

        if ( ! $order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        $existingShipment = Shipment::where('order_id', $order->id)
                                    ->where('order_source', Shipment::SOURCE_FLUENT_CART)
                                    ->first();

        if ($existingShipment) {
            return [
                'success'  => false,
                'message'  => 'Shipment already exists for this order',
                'shipment' => $existingShipment,
            ];
        }

        $options = [
            'carrier' => $request->getSafe('carrier', 'sanitize_text_field', Shipment::CARRIER_CUSTOM),
            'service' => $request->getSafe('service', 'sanitize_text_field', 'standard'),
        ];

        if ($request->getSafe('estimated_delivery', 'sanitize_text_field')) {
            $options['estimated_delivery'] = $request->getSafe('estimated_delivery', 'sanitize_text_field');
        }

        $shipment = ShipmentService::createFromFluentCartOrder($order, $options);

        if ( ! $shipment) {
            return [
                'success' => false,
                'message' => 'Failed to create shipment. Order may not be eligible for shipping.',
            ];
        }

        return [
            'success'  => true,
            'message'  => 'Shipment created successfully',
            'shipment' => $shipment->load('trackingEvents'),
        ];
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'        => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'location'      => 'string|max:255',
            'description'   => 'string|max:500',
            'rider_id'      => 'nullable|integer|exists:fluent_shipment_riders,id',
            'sync_to_order' => 'boolean',
        ]);

        $shipment = Shipment::findOrFail($id);

        $newStatus   = $request->getSafe('status', 'sanitize_text_field');
        $location    = $request->getSafe('location', 'sanitize_text_field');
        $description = $request->getSafe('description', 'sanitize_text_field');
        $riderId     = $request->getSafe('rider_id' , 'intval');
        $syncToOrder = $request->getSafe('sync_to_order', 'rest_sanitize_boolean', true);

        if ($newStatus === Shipment::STATUS_OUT_FOR_DELIVERY && $riderId) {
            $shipment->rider_id = $riderId;
            $shipment->save();

            $rider = Rider::find($riderId);
            if ($rider) {
                $rider->increment('total_deliveries');
            }
        }

        $eventData = array_filter([
            'location'    => $location,
            'description' => $description,
            'date'        => DateTimeHelper::now(),
        ]);

        $success = $shipment->updateStatus($newStatus, $eventData);

        if ( ! $success) {
            return [
                'success' => false,
                'message' => 'Failed to update shipment status',
            ];
        }

        if ($syncToOrder) {
            ShipmentService::syncToFluentCartOrder($shipment);
        }

        return [
            'success'  => true,
            'message'  => 'Shipment status updated successfully',
            'shipment' => $shipment->load('trackingEvents'),
        ];
    }

    public function updateTrackingNumber(Request $request, $id)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'carrier'         => 'string|in:' . implode(',', Shipment::getCarriers()),
            'tracking_url'    => 'url|max:500',
        ]);

        $shipment = Shipment::findOrFail($id);

        $oldTrackingNumber         = $shipment->tracking_number;
        $shipment->tracking_number = $request->getSafe('tracking_number', 'sanitize_text_field');

        if ($request->get('carrier')) {
            $shipment->carrier = $request->getSafe('carrier', 'sanitize_text_field');
        }

        if ($request->get('tracking_url')) {
            $shipment->tracking_url = $request->getSafe('tracking_url', 'sanitize_url');
        }

        $shipment->save();

        $shipment->createTrackingEvent($shipment->current_status, [
            'description' => "Tracking number updated from {$oldTrackingNumber} to {$shipment->tracking_number}",
            'location'    => 'System Update',
        ]);

        return [
            'success'  => true,
            'message'  => 'Tracking number updated successfully',
            'shipment' => $shipment->fresh(),
        ];
    }

    public function show($id)
    {
        $shipment = Shipment::with([
            'trackingEvents' => function ($query) {
                $query->orderBy('event_date', 'desc')->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        return [
            'success'      => true,
            'shipment'     => $shipment,
            'tracking_url' => $shipment->getTrackingUrl(),
        ];
    }

    public function trackingEvents($id)
    {
        $shipment = Shipment::findOrFail($id);

        $events = ShipmentTrackingEvent::forShipment($id)
                                       ->orderBy('event_date', 'desc')
                                       ->orderBy('created_at', 'desc')
                                       ->get();

        return [
            'success'         => true,
            'shipment_id'     => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'current_status'  => $shipment->current_status,
            'events'          => $events,
        ];
    }

    public function addTrackingEvent(Request $request, $id)
    {
        $request->validate([
            'status'       => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'description'  => 'string|max:500',
            'location'     => 'string|max:255',
            'event_date'   => 'date',
            'is_milestone' => 'boolean',
        ]);

        $shipment = Shipment::findOrFail($id);

        $eventData = [
            'description'  => $request->getSafe('description', 'sanitize_text_field') ?? Shipment::getStatusLabels()[$request->getSafe('status', 'sanitize_text_field')],
            'location'     => $request->getSafe('location', 'sanitize_text_field'),
            'date'         => $request->getSafe('event_date', 'sanitize_text_field') ?? DateTimeHelper::now(),
            'is_milestone' => $request->getSafe('is_milestone', 'rest_sanitize_boolean', Shipment::isMilestoneStatus($request->getSafe('status', 'sanitize_text_field'))),
        ];

        $event = $shipment->createTrackingEvent($request->getSafe('status', 'sanitize_text_field'), $eventData);

        return [
            'success' => true,
            'message' => 'Tracking event added successfully',
            'event'   => $event,
        ];
    }

    public function stats(Request $request)
    {
        $dateFrom = $request->getSafe('date_from', 'sanitize_text_field');
        $dateTo   = $request->getSafe('date_to', 'sanitize_text_field');

        $query = Shipment::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $totalShipments = $query->count();

        $statusCounts = $query->selectRaw('current_status, COUNT(*) as count')
                              ->groupBy('current_status')
                              ->pluck('count', 'current_status')
                              ->toArray();

        $carrierCounts = $query->selectRaw('carrier, COUNT(*) as count')
                               ->groupBy('carrier')
                               ->pluck('count', 'carrier')
                               ->toArray();

        $sourceoCounts = $query->selectRaw('order_source, COUNT(*) as count')
                               ->groupBy('order_source')
                               ->pluck('count', 'order_source')
                               ->toArray();

        // Recent shipments
        $recentShipments = Shipment::orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get();

        return [
            'success'           => true,
            'period'            => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'total_shipments'   => $totalShipments,
            'status_breakdown'  => $statusCounts,
            'carrier_breakdown' => $carrierCounts,
            'source_breakdown'  => $sourceoCounts,
            'recent_shipments'  => $recentShipments,
        ];
    }

    public function delete($id)
    {
        $shipment = Shipment::findOrFail($id);

        $trackingNumber = $shipment->tracking_number;

        ShipmentTrackingEvent::where('shipment_id', $id)->delete();

        $shipment->delete();

        return [
            'success' => true,
            'message' => "Shipment {$trackingNumber} deleted successfully",
        ];
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'shipment_ids'   => 'required|array|min:1',
            'shipment_ids.*' => 'integer|exists:fluent_shipments,id',
            'status'         => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'location'       => 'string|max:255',
            'description'    => 'string|max:500',
        ]);

        $shipmentIds = $request->getSafe('shipment_ids', 'fluentShipmentSanitizeIds');
        $status      = $request->getSafe('status', 'sanitize_text_field');
        $location    = $request->getSafe('location', 'sanitize_text_field');
        $description = $request->getSafe('description', 'sanitize_text_field');

        $shipments = Shipment::whereIn('id', $shipmentIds)->get();

        $updated = [];
        $failed  = [];

        foreach ($shipments as $shipment) {
            $eventData = array_filter([
                'location'    => $location,
                'description' => $description,
                'date'        => DateTimeHelper::now(),
            ]);

            if ($shipment->updateStatus($status, $eventData)) {
                $updated[] = $shipment->id;

                ShipmentService::syncToFluentCartOrder($shipment);
            } else {
                $failed[] = $shipment->id;
            }
        }

        return [
            'success' => true,
            'message' => sprintf('Updated %d of %d shipments', count($updated), count($shipmentIds)),
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }
}
