<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Rider;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Http\Request\Request;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->getSafe('per_page', 'intval', 15), 100);
        $page    = $request->getSafe('page', 'intval', 1);

        $status      = $request->getSafe('status', 'sanitize_text_field');
        $vehicleType = $request->getSafe('vehicle_type', 'sanitize_text_field');
        $search      = $request->getSafe('search', 'sanitize_text_field');
        $dateFrom    = $request->getSafe('date_from', 'sanitize_text_field');
        $dateTo      = $request->getSafe('date_to', 'sanitize_text_field');

        $query = Rider::query();

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

        if ($search) {
            $query->search($search);
        }

        $riders = $query->orderBy('created_at', 'DESC')
                        ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success'         => true,
            'data'            => $riders->toArray(),
            'filters_applied' => compact('status', 'vehicleType', 'search', 'dateFrom', 'dateTo'),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'rider_name'                 => 'required|string|max:100',
            'email'                      => 'required|email|max:100|unique:fluent_shipment_riders,email',
            'phone'                      => 'nullable|string|max:20',
            'license_number'             => 'nullable|string|max:50',
            'vehicle_type'               => 'nullable|string|in:' . implode(',', Rider::getVehicleTypes()),
            'vehicle_number'             => 'nullable|string|max:50',
            'status'                     => 'nullable|string|in:' . implode(',', Rider::getStatuses()),
            'joining_date'               => 'nullable|date',
            'address'                    => 'nullable|array',
            'address.street'             => 'nullable|string|max:255',
            'address.city'               => 'nullable|string|max:100',
            'address.state'              => 'nullable|string|max:100',
            'address.postcode'           => 'nullable|string|max:20',
            'address.country'            => 'nullable|string|max:100',
            'emergency_contact'          => 'nullable|array',
            'emergency_contact.name'     => 'nullable|string|max:100',
            'emergency_contact.phone'    => 'nullable|string|max:20',
            'emergency_contact.relation' => 'nullable|string|max:50',
            'notes'                      => 'nullable|string',
        ]);

        $riderData = [
            'rider_name'            => $request->getSafe('rider_name', 'sanitize_text_field'),
            'email'                 => $request->getSafe('email', 'sanitize_email'),
            'phone'                 => $request->getSafe('phone', 'sanitize_text_field'),
            'license_number'        => $request->getSafe('license_number', 'sanitize_text_field'),
            'vehicle_type'          => $request->getSafe('vehicle_type', 'sanitize_text_field', Rider::VEHICLE_BIKE),
            'vehicle_number'        => $request->getSafe('vehicle_number', 'sanitize_text_field'),
            'status'                => $request->getSafe('status', 'sanitize_text_field', Rider::STATUS_ACTIVE),
            'joining_date'          => $request->getSafe('joining_date', 'sanitize_text_field') ?: DateTimeHelper::now(),
            'address'               => $request->getSafe('address', 'fluentShipmentSanitizeArray'),
            'emergency_contact'     => $request->getSafe('emergency_contact', 'fluentShipmentSanitizeArray'),
            'notes'                 => $request->getSafe('notes', 'sanitize_text_field'),
            'rating'                => 0.0,
            'total_deliveries'      => 0,
            'successful_deliveries' => 0,
        ];

        $rider = Rider::create($riderData);

        if ( ! $rider) {
            return [
                'success' => false,
                'message' => 'Failed to create rider',
            ];
        }

        return [
            'success' => true,
            'message' => 'Rider created successfully',
            'rider'   => $rider->fresh(),
        ];
    }

    public function show($id)
    {
        $rider = Rider::findOrFail($id);

        return [
            'success' => true,
            'rider'   => $rider,
        ];
    }

    public function update(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);

        $request->validate([
            'rider_name'        => 'required|string|max:100',
            'email'             => 'required|email|max:100|unique:fluent_shipment_riders,email,' . $id,
            'phone'             => 'nullable|string|max:20',
            'license_number'    => 'nullable|string|max:50',
            'vehicle_type'      => 'nullable|string|in:' . implode(',', Rider::getVehicleTypes()),
            'vehicle_number'    => 'nullable|string|max:50',
            'status'            => 'nullable|string|in:' . implode(',', Rider::getStatuses()),
            'joining_date'      => 'nullable|date',
            'address'           => 'nullable|array',
            'emergency_contact' => 'nullable|array',
            'notes'             => 'nullable|string',
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
            'rider'   => $rider->fresh(),
        ];
    }

    public function updateRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
        ]);

        $rider = Rider::findOrFail($id);

        $success = $rider->updateRating($request->get('rating'));

        if ( ! $success) {
            return [
                'success' => false,
                'message' => 'Failed to update rider rating',
            ];
        }

        return [
            'success' => true,
            'message' => 'Rider rating updated successfully',
            'rider'   => $rider->fresh(),
        ];
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Rider::getStatuses()),
        ]);

        $rider         = Rider::findOrFail($id);
        $rider->status = $request->get('status');
        $rider->save();

        return [
            'success' => true,
            'message' => 'Rider status updated successfully',
            'rider'   => $rider->fresh(),
        ];
    }

    public function delete($id)
    {
        $rider = Rider::findOrFail($id);

        $riderName = $rider->rider_name;

        $rider->delete();

        return [
            'success' => true,
            'message' => "Rider {$riderName} deleted successfully",
        ];
    }

    public function stats(Request $request)
    {
        $dateFrom = $request->getSafe('date_from', 'sanitize_text_field');
        $dateTo   = $request->getSafe('date_to', 'sanitize_text_field');

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

        $avgRating = Rider::where('rating', '>', 0)->avg('rating');

        $topRiders = Rider::where('total_deliveries', '>', 0)
                          ->orderBy('rating', 'desc')
                          ->orderBy('success_rate', 'desc')
                          ->limit(5)
                          ->get();

        return [
            'success'                => true,
            'period'                 => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'total_riders'           => $totalRiders,
            'status_breakdown'       => $statusCounts,
            'vehicle_type_breakdown' => $vehicleTypeCounts,
            'average_rating'         => round($avgRating ?? 0, 2),
            'top_riders'             => $topRiders,
        ];
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'rider_ids'   => 'required|array|min:1',
            'rider_ids.*' => 'integer|exists:fluent_shipment_riders,id',
            'status'      => 'required|string|in:' . implode(',', Rider::getStatuses()),
        ]);

        $riderIds = $request->get('rider_ids');
        $status   = $request->getSafe('status', 'sanitize_text_field');

        $riders = Rider::whereIn('id', $riderIds)->get();

        $updated = [];
        $failed  = [];

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
            'failed'  => $failed,
        ];
    }

    public function getActiveRiders()
    {
        $riders = Rider::active()
                       ->orderBy('rating', 'desc')
                       ->orderBy('rider_name', 'asc')
                       ->get(['id', 'rider_name', 'email', 'phone', 'vehicle_type', 'rating', 'total_deliveries']);

        return [
            'success' => true,
            'riders'  => $riders,
        ];
    }

    public function search(Request $request)
    {
        $request->validate([
            'q'     => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->getSafe('q', 'sanitize_text_field');
        $limit = $request->getSafe('limit', 'intval', 10);

        $riders = Rider::search($query)
                       ->active()
                       ->limit($limit)
                       ->get(['id', 'rider_name', 'email', 'phone', 'vehicle_type', 'rating']);

        return [
            'success' => true,
            'riders'  => $riders,
        ];
    }
}
