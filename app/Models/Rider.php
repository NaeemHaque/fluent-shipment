<?php

namespace FluentShipment\App\Models;

use FluentShipment\App\Models\Model;
use FluentShipment\App\Helpers\DateTimeHelper;

class Rider extends Model
{
    protected $table = 'fluent_shipment_riders';
    protected $primaryKey = 'id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Rider status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    // Vehicle types
    const VEHICLE_BIKE = 'bike';
    const VEHICLE_MOTORCYCLE = 'motorcycle';
    const VEHICLE_CAR = 'car';
    const VEHICLE_VAN = 'van';
    const VEHICLE_TRUCK = 'truck';

    protected $fillable = [
        'rider_name',
        'email',
        'phone',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'status',
        'address',
        'emergency_contact',
        'documents',
        'rating',
        'total_deliveries',
        'successful_deliveries',
        'avatar_url',
        'notes',
        'joining_date',
        'meta',
    ];

    protected $casts = [
        'rating'                => 'float',
        'total_deliveries'      => 'integer',
        'successful_deliveries' => 'integer',
        'joining_date'          => 'date',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'address'               => 'array',
        'emergency_contact'     => 'array',
        'documents'             => 'array',
        'meta'                  => 'array',
    ];

    protected $appends = [
        'status_label',
        'vehicle_type_label',
        'success_rate',
        'formatted_rating',
        'full_profile',
    ];

    public function getStatusLabelAttribute(): string
    {
        return static::getStatusLabels()[$this->status] ?? ucfirst($this->status);
    }

    public function getVehicleTypeLabelAttribute(): string
    {
        return static::getVehicleTypeLabels()[$this->vehicle_type] ?? ucfirst($this->vehicle_type);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_deliveries == 0) {
            return 0.0;
        }

        return round(($this->successful_deliveries / $this->total_deliveries) * 100, 2);
    }

    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1) . '/5.0';
    }

    public function getFullProfileAttribute(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->rider_name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'status'           => $this->status,
            'status_label'     => $this->status_label,
            'vehicle_info'     => $this->vehicle_type_label . ($this->vehicle_number ? " ({$this->vehicle_number})" : ''),
            'rating'           => $this->formatted_rating,
            'success_rate'     => $this->success_rate . '%',
            'total_deliveries' => $this->total_deliveries,
            'avatar_url'       => $this->avatar_url,
        ];
    }

    public static function getStatuses(): array
    {
        return [
            static::STATUS_ACTIVE,
            static::STATUS_INACTIVE,
            static::STATUS_SUSPENDED,
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            static::STATUS_ACTIVE    => 'Active',
            static::STATUS_INACTIVE  => 'Inactive',
            static::STATUS_SUSPENDED => 'Suspended',
        ];
    }

    public static function getVehicleTypes(): array
    {
        return [
            static::VEHICLE_BIKE,
            static::VEHICLE_MOTORCYCLE,
            static::VEHICLE_CAR,
            static::VEHICLE_VAN,
            static::VEHICLE_TRUCK,
        ];
    }

    public static function getVehicleTypeLabels(): array
    {
        return [
            static::VEHICLE_BIKE       => 'Bike',
            static::VEHICLE_MOTORCYCLE => 'Motorcycle',
            static::VEHICLE_CAR        => 'Car',
            static::VEHICLE_VAN        => 'Van',
            static::VEHICLE_TRUCK      => 'Truck',
        ];
    }

    public function updateRating(float $newRating): bool
    {
        $this->rating = max(0, min(5, $newRating));

        return $this->save();
    }

    public function incrementDeliveries(bool $successful = true): bool
    {
        $this->total_deliveries++;

        if ($successful) {
            $this->successful_deliveries++;
        }

        return $this->save();
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfVehicleType($query, $vehicleType)
    {
        return $query->where('vehicle_type', $vehicleType);
    }

    public function scopeActive($query)
    {
        return $query->where('status', static::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', static::STATUS_INACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', static::STATUS_SUSPENDED);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('rider_name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('vehicle_number', 'LIKE', "%{$search}%")
              ->orWhere('license_number', 'LIKE', "%{$search}%");
        });
    }

    public function scopeTopRated($query, $limit = 10)
    {
        return $query->orderBy('rating', 'desc')
                     ->limit($limit);
    }

    public function getFormattedAddress(): string
    {
        if ( ! $this->address) {
            return 'N/A';
        }

        $parts = array_filter([
            $this->address['street'] ?? '',
            $this->address['city'] ?? '',
            $this->address['state'] ?? '',
            $this->address['postcode'] ?? '',
            $this->address['country'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    public function getEmergencyContactInfo(): string
    {
        if ( ! $this->emergency_contact) {
            return 'N/A';
        }

        $name     = $this->emergency_contact['name'] ?? '';
        $phone    = $this->emergency_contact['phone'] ?? '';
        $relation = $this->emergency_contact['relation'] ?? '';

        $info = $name;
        if ($phone) {
            $info .= " ({$phone})";
        }
        if ($relation) {
            $info .= " - {$relation}";
        }

        return $info ?: 'N/A';
    }

    public function isAvailable(): bool
    {
        return $this->status === static::STATUS_ACTIVE;
    }
}
