<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\ShipmentTrackingEvent;
use FluentShipment\App\Services\ShipmentService;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Http\Request\Request;

class ShipmentController extends Controller
{
    /**
     * Get all shipments with advanced filtering
     * 
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 15), 100); // Cap at 100
        $page = $request->get('page', 1);
        
        // Filters
        $status = $request->get('status');
        $orderSource = $request->get('order_source');
        $trackingNumber = $request->get('tracking_number');
        $customerEmail = $request->get('customer_email');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');
        
        // Include relationships
        $with = $request->get('with', []);
        $allowedWith = ['trackingEvents', 'latestTrackingEvent'];
        $with = array_intersect((array)$with, $allowedWith);

        $query = Shipment::query();

        // Apply filters
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

        // General search across multiple fields
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%")
                  ->orWhere('order_id', 'LIKE', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(delivery_address, '$.name') LIKE ?", ["%{$search}%"]);
            });
        }

        // Include relationships if requested
        if (!empty($with)) {
            $query->with($with);
        }

        $shipments = $query->orderBy('created_at', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $shipments->toArray(),
            'filters_applied' => compact('status', 'orderSource', 'trackingNumber', 'customerEmail', 'dateFrom', 'dateTo', 'search'),
        ];
    }

    /**
     * Bulk import shipments from fluent-cart orders
     * 
     * @param Request $request
     * @return array
     */
    public function importFromFluentCart(Request $request)
    {
        // Check if fluent-cart is available
        if (!class_exists('\FluentCart\App\Models\Order')) {
            return [
                'success' => false,
                'message' => 'FluentCart plugin is not active',
            ];
        }

        $request->validate([
            'filters' => 'array',
            'options' => 'array',
        ]);

        $filters = $request->get('filters', []);
        $options = $request->get('options', []);

        // Get fluent-cart status constants
        $statusClass = '\FluentCart\App\Helpers\Status';
        $orderClass = '\FluentCart\App\Models\Order';

        // Build query for eligible orders
        $query = $orderClass::with(['shipping_address', 'customer', 'order_items'])
            ->where('fulfillment_type', $statusClass::FULFILLMENT_TYPE_PHYSICAL)
            ->whereIn('payment_status', [$statusClass::PAYMENT_PAID, $statusClass::PAYMENT_PARTIALLY_PAID])
            ->whereIn('status', [$statusClass::ORDER_PROCESSING, $statusClass::ORDER_COMPLETED])
            ->whereHas('shipping_address')
            ->where('shipping_status', '!=', $statusClass::SHIPPING_UNSHIPPABLE);

        // Apply additional filters
        if (!empty($filters['order_status'])) {
            $query->where('status', $filters['order_status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['shipping_status'])) {
            $query->where('shipping_status', $filters['shipping_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Get orders
        $orders = $query->get();

        // Filter out orders that already have shipments
        $orderIds = $orders->pluck('id')->toArray();
        $existingShipmentOrderIds = Shipment::where('order_source', Shipment::SOURCE_FLUENT_CART)
            ->whereIn('order_id', $orderIds)
            ->pluck('order_id')
            ->toArray();

        $newOrderIds = array_diff($orderIds, $existingShipmentOrderIds);

        // Create shipments for eligible orders
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
            'success' => true,
            'total_orders_found' => $orders->count(),
            'existing_shipments' => count($existingShipmentOrderIds),
            'eligible_for_import' => count($newOrderIds),
            'results' => $results,
        ];
    }

    /**
     * Create shipment from specific fluent-cart order
     * 
     * @param Request $request
     * @param int $orderId
     * @return array
     */
    public function createFromFluentCartOrder(Request $request, $orderId)
    {
        // Check if fluent-cart is available
        if (!class_exists('\FluentCart\App\Models\Order')) {
            return [
                'success' => false,
                'message' => 'FluentCart plugin is not active',
            ];
        }

        $request->validate([
            'carrier' => 'string|in:' . implode(',', Shipment::getCarriers()),
            'service' => 'string|max:100',
            'estimated_delivery' => 'date|after:today',
        ]);

        $orderClass = '\FluentCart\App\Models\Order';
        $order = $orderClass::with(['shipping_address', 'customer', 'order_items'])->find($orderId);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        // Check if shipment already exists
        $existingShipment = Shipment::where('order_id', $order->id)
            ->where('order_source', Shipment::SOURCE_FLUENT_CART)
            ->first();

        if ($existingShipment) {
            return [
                'success' => false,
                'message' => 'Shipment already exists for this order',
                'shipment' => $existingShipment,
            ];
        }

        // Prepare options
        $options = [
            'carrier' => $request->get('carrier', Shipment::CARRIER_CUSTOM),
            'service' => $request->get('service', 'standard'),
        ];

        if ($request->get('estimated_delivery')) {
            $options['estimated_delivery'] = $request->get('estimated_delivery');
        }

        // Create shipment
        $shipment = ShipmentService::createFromFluentCartOrder($order, $options);

        if (!$shipment) {
            return [
                'success' => false,
                'message' => 'Failed to create shipment. Order may not be eligible for shipping.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Shipment created successfully',
            'shipment' => $shipment->load('trackingEvents'),
        ];
    }

    /**
     * Update shipment status
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'location' => 'string|max:255',
            'description' => 'string|max:500',
            'sync_to_order' => 'boolean',
        ]);

        $shipment = Shipment::findOrFail($id);
        
        $newStatus = $request->get('status');
        $location = $request->get('location');
        $description = $request->get('description');
        $syncToOrder = $request->get('sync_to_order', true);

        // Update shipment status
        $eventData = array_filter([
            'location' => $location,
            'description' => $description,
            'date' => DateTimeHelper::now(),
        ]);

        $success = $shipment->updateStatus($newStatus, $eventData);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to update shipment status',
            ];
        }

        // Sync back to fluent-cart order if requested
        if ($syncToOrder) {
            ShipmentService::syncToFluentCartOrder($shipment);
        }

        return [
            'success' => true,
            'message' => 'Shipment status updated successfully',
            'shipment' => $shipment->load('trackingEvents'),
        ];
    }

    /**
     * Update tracking number
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateTrackingNumber(Request $request, $id)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'carrier' => 'string|in:' . implode(',', Shipment::getCarriers()),
            'tracking_url' => 'url|max:500',
        ]);

        $shipment = Shipment::findOrFail($id);
        
        $oldTrackingNumber = $shipment->tracking_number;
        $shipment->tracking_number = $request->get('tracking_number');
        
        if ($request->get('carrier')) {
            $shipment->carrier = $request->get('carrier');
        }
        
        if ($request->get('tracking_url')) {
            $shipment->tracking_url = $request->get('tracking_url');
        }
        
        $shipment->save();

        // Create tracking event
        $shipment->createTrackingEvent($shipment->current_status, [
            'description' => "Tracking number updated from {$oldTrackingNumber} to {$shipment->tracking_number}",
            'location' => 'System Update',
        ]);

        return [
            'success' => true,
            'message' => 'Tracking number updated successfully',
            'shipment' => $shipment->fresh(),
        ];
    }

    /**
     * Get single shipment with tracking events
     * 
     * @param int $id
     * @return array
     */
    public function show($id)
    {
        $shipment = Shipment::with(['trackingEvents' => function ($query) {
            $query->orderBy('event_date', 'desc')->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return [
            'success' => true,
            'shipment' => $shipment,
            'tracking_url' => $shipment->getTrackingUrl(),
        ];
    }

    /**
     * Get shipment tracking events
     * 
     * @param int $id
     * @return array
     */
    public function trackingEvents($id)
    {
        $shipment = Shipment::findOrFail($id);
        
        $events = ShipmentTrackingEvent::forShipment($id)
            ->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'current_status' => $shipment->current_status,
            'events' => $events,
        ];
    }

    /**
     * Add tracking event
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function addTrackingEvent(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'description' => 'string|max:500',
            'location' => 'string|max:255',
            'event_date' => 'date',
            'is_milestone' => 'boolean',
        ]);

        $shipment = Shipment::findOrFail($id);
        
        $eventData = [
            'description' => $request->get('description') ?? Shipment::getStatusLabels()[$request->get('status')],
            'location' => $request->get('location'),
            'date' => $request->get('event_date') ?? DateTimeHelper::now(),
            'is_milestone' => $request->get('is_milestone', Shipment::isMilestoneStatus($request->get('status'))),
        ];

        $event = $shipment->createTrackingEvent($request->get('status'), $eventData);

        return [
            'success' => true,
            'message' => 'Tracking event added successfully',
            'event' => $event,
        ];
    }

    /**
     * Get shipment statistics
     * 
     * @param Request $request
     * @return array
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

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
            'success' => true,
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'total_shipments' => $totalShipments,
            'status_breakdown' => $statusCounts,
            'carrier_breakdown' => $carrierCounts,
            'source_breakdown' => $sourceoCounts,
            'recent_shipments' => $recentShipments,
        ];
    }

    /**
     * Delete shipment
     * 
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        $shipment = Shipment::findOrFail($id);
        
        // Store for response
        $trackingNumber = $shipment->tracking_number;
        
        // Delete related tracking events first (if not using foreign key cascade)
        ShipmentTrackingEvent::where('shipment_id', $id)->delete();
        
        // Delete shipment
        $shipment->delete();

        return [
            'success' => true,
            'message' => "Shipment {$trackingNumber} deleted successfully",
        ];
    }

    /**
     * Bulk update shipment statuses
     * 
     * @param Request $request
     * @return array
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'integer|exists:fluent_shipments,id',
            'status' => 'required|string|in:' . implode(',', Shipment::getStatuses()),
            'location' => 'string|max:255',
            'description' => 'string|max:500',
        ]);

        $shipmentIds = $request->get('shipment_ids');
        $status = $request->get('status');
        $location = $request->get('location');
        $description = $request->get('description');

        $shipments = Shipment::whereIn('id', $shipmentIds)->get();
        
        $updated = [];
        $failed = [];

        foreach ($shipments as $shipment) {
            $eventData = array_filter([
                'location' => $location,
                'description' => $description,
                'date' => DateTimeHelper::now(),
            ]);

            if ($shipment->updateStatus($status, $eventData)) {
                $updated[] = $shipment->id;
                
                // Sync to fluent-cart if applicable
                ShipmentService::syncToFluentCartOrder($shipment);
            } else {
                $failed[] = $shipment->id;
            }
        }

        return [
            'success' => true,
            'message' => sprintf('Updated %d of %d shipments', count($updated), count($shipmentIds)),
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * Generate tracking numbers for shipments without them
     * 
     * @param Request $request
     * @return array
     */
    public function generateTrackingNumbers(Request $request)
    {
        $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'integer|exists:fluent_shipments,id',
            'carrier' => 'string|in:' . implode(',', Shipment::getCarriers()),
        ]);

        $shipmentIds = $request->get('shipment_ids');

        $shipments = Shipment::whereIn('id', $shipmentIds)
            ->where(function ($query) {
                $query->whereNull('tracking_number')
                      ->orWhere('tracking_number', '');
            })
            ->get();

        $updated = [];

        foreach ($shipments as $shipment) {
            $oldTrackingNumber = $shipment->tracking_number;
            $shipment->tracking_number = ShipmentService::generateTrackingNumber($carrier ?: $shipment->carrier);
            
            if ($carrier) {
                $shipment->carrier = $carrier;
            }
            
            $shipment->save();
            
            // Create tracking event
            $shipment->createTrackingEvent($shipment->current_status, [
                'description' => $oldTrackingNumber 
                    ? "Tracking number updated to {$shipment->tracking_number}"
                    : "Tracking number generated: {$shipment->tracking_number}",
                'location' => 'System Update',
            ]);

            $updated[] = [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
            ];
        }

        return [
            'success' => true,
            'message' => sprintf('Generated tracking numbers for %d shipments', count($updated)),
            'updated' => $updated,
        ];
    }

}
