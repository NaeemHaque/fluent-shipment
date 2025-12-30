<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Rider;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Http\Request\Request;

class RiderController extends Controller
{
    /**
     * Get all riders with advanced filtering
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
        $vehicleType = $request->get('vehicle_type');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $query = Rider::query();

        // Apply filters
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        if ($vehicleType) {
            $query->where('vehicle_type', $vehicleType);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // General search across multiple fields
        if ($search) {
            $query->search($search);
        }

        $riders = $query->orderBy('created_at', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $riders->toArray(),
            'filters_applied' => compact('status', 'vehicleType', 'search', 'dateFrom', 'dateTo'),
        ];
    }

    /**
     * Create a new rider
     * 
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $request->validate([
            'rider_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:fluent_shipment_riders,email',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|in:' . implode(',', Rider::getVehicleTypes()),
            'vehicle_number' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:' . implode(',', Rider::getStatuses()),
            'joining_date' => 'nullable|date',
            'address' => 'nullable|array',
            'address.street' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.postcode' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|array',
            'emergency_contact.name' => 'nullable|string|max:100',
            'emergency_contact.phone' => 'nullable|string|max:20',
            'emergency_contact.relation' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $riderData = [
            'rider_name' => $request->get('rider_name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'license_number' => $request->get('license_number'),
            'vehicle_type' => $request->get('vehicle_type', Rider::VEHICLE_BIKE),
            'vehicle_number' => $request->get('vehicle_number'),
            'status' => $request->get('status', Rider::STATUS_ACTIVE),
            'joining_date' => $request->get('joining_date') ?: DateTimeHelper::now(),
            'address' => $request->get('address'),
            'emergency_contact' => $request->get('emergency_contact'),
            'notes' => $request->get('notes'),
            'rating' => 0.0,
            'total_deliveries' => 0,
            'successful_deliveries' => 0,
        ];

        $rider = Rider::create($riderData);

        if (!$rider) {
            return [
                'success' => false,
                'message' => 'Failed to create rider',
            ];
        }

        return [
            'success' => true,
            'message' => 'Rider created successfully',
            'rider' => $rider->fresh(),
        ];
    }

    /**
     * Get single rider details
     * 
     * @param int $id
     * @return array
     */
    public function show($id)
    {
        $rider = Rider::findOrFail($id);

        return [
            'success' => true,
            'rider' => $rider,
        ];
    }

    /**
     * Update rider information
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);

        $request->validate([
            'rider_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:fluent_shipment_riders,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|in:' . implode(',', Rider::getVehicleTypes()),
            'vehicle_number' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:' . implode(',', Rider::getStatuses()),
            'joining_date' => 'nullable|date',
            'address' => 'nullable|array',
            'emergency_contact' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $rider->fill($request->only([
            'rider_name',
            'email',
            'phone',
            'license_number',
            'vehicle_type',
            'vehicle_number',
            'status',
            'joining_date',
            'address',
            'emergency_contact',
            'notes',
        ]));

        $rider->save();

        return [
            'success' => true,
            'message' => 'Rider updated successfully',
            'rider' => $rider->fresh(),
        ];
    }

    /**
     * Update rider rating
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
        ]);

        $rider = Rider::findOrFail($id);
        
        $success = $rider->updateRating($request->get('rating'));

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to update rider rating',
            ];
        }

        return [
            'success' => true,
            'message' => 'Rider rating updated successfully',
            'rider' => $rider->fresh(),
        ];
    }

    /**
     * Update rider status
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Rider::getStatuses()),
        ]);

        $rider = Rider::findOrFail($id);
        $rider->status = $request->get('status');
        $rider->save();

        return [
            'success' => true,
            'message' => 'Rider status updated successfully',
            'rider' => $rider->fresh(),
        ];
    }

    /**
     * Delete rider
     * 
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        $rider = Rider::findOrFail($id);
        
        // Store for response
        $riderName = $rider->rider_name;
        
        // Delete rider
        $rider->delete();

        return [
            'success' => true,
            'message' => "Rider {$riderName} deleted successfully",
        ];
    }

    /**
     * Get rider statistics
     * 
     * @param Request $request
     * @return array
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Rider::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $totalRiders = $query->count();
        
        $statusCounts = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $vehicleTypeCounts = $query->selectRaw('vehicle_type, COUNT(*) as count')
            ->groupBy('vehicle_type')
            ->pluck('count', 'vehicle_type')
            ->toArray();

        // Get average rating
        $avgRating = Rider::where('rating', '>', 0)->avg('rating');

        // Get top performers
        $topRiders = Rider::where('total_deliveries', '>', 0)
            ->orderBy('rating', 'desc')
            ->orderBy('success_rate', 'desc')
            ->limit(5)
            ->get();

        return [
            'success' => true,
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'total_riders' => $totalRiders,
            'status_breakdown' => $statusCounts,
            'vehicle_type_breakdown' => $vehicleTypeCounts,
            'average_rating' => round($avgRating ?? 0, 2),
            'top_riders' => $topRiders,
        ];
    }

    /**
     * Bulk update rider statuses
     * 
     * @param Request $request
     * @return array
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'rider_ids' => 'required|array|min:1',
            'rider_ids.*' => 'integer|exists:fluent_shipment_riders,id',
            'status' => 'required|string|in:' . implode(',', Rider::getStatuses()),
        ]);

        $riderIds = $request->get('rider_ids');
        $status = $request->get('status');

        $riders = Rider::whereIn('id', $riderIds)->get();
        
        $updated = [];
        $failed = [];

        foreach ($riders as $rider) {
            $rider->status = $status;
            
            if ($rider->save()) {
                $updated[] = $rider->id;
            } else {
                $failed[] = $rider->id;
            }
        }

        return [
            'success' => true,
            'message' => sprintf('Updated %d of %d riders', count($updated), count($riderIds)),
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * Get active riders for assignment
     * 
     * @return array
     */
    public function getActiveRiders()
    {
        $riders = Rider::active()
            ->orderBy('rating', 'desc')
            ->orderBy('rider_name', 'asc')
            ->get(['id', 'rider_name', 'email', 'phone', 'vehicle_type', 'rating', 'total_deliveries']);

        return [
            'success' => true,
            'riders' => $riders,
        ];
    }

    /**
     * Search riders for quick selection
     * 
     * @param Request $request
     * @return array
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        $riders = Rider::search($query)
            ->active()
            ->limit($limit)
            ->get(['id', 'rider_name', 'email', 'phone', 'vehicle_type', 'rating']);

        return [
            'success' => true,
            'riders' => $riders,
        ];
    }
}