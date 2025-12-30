<?php

namespace FluentShipment\App\Models;

use FluentShipment\App\Models\Model;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Database\Orm\Relations\HasMany;

class Shipment extends Model
{
    protected $table = 'fluent_shipments';
    protected $primaryKey = 'id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RETURNED = 'returned';
    const STATUS_EXCEPTION = 'exception';

    // Order sources
    const SOURCE_FLUENT_CART = 'fluent-cart';
    const SOURCE_WOOCOMMERCE = 'woocommerce';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_API = 'api';


    protected $fillable = [
        'order_id',
        'order_source',
        'order_hash',
        'tracking_number',
        'current_status',
        'shipping_address',
        'delivery_address',
        'package_info',
        'estimated_delivery',
        'shipped_at',
        'delivered_at',
        'customer_id',
        'customer_email',
        'customer_phone',
        'rider_id',
        'weight_total',
        'dimensions',
        'shipping_cost',
        'currency',
        'tracking_url',
        'delivery_confirmation',
        'special_instructions',
        'meta',
    ];

    protected $casts = [
        'order_id'           => 'integer',
        'customer_id'        => 'integer',
        'shipping_cost'      => 'integer',
        'weight_total'       => 'float',
        'estimated_delivery' => 'date',
        'shipped_at'         => 'datetime',
        'delivered_at'       => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'shipping_address'   => 'array',
        'delivery_address'   => 'array',
        'package_info'       => 'array',
        'dimensions'         => 'array',
        'meta'               => 'array',
    ];

    protected $appends = [
        'status_label',
        'formatted_shipping_cost',
        'is_trackable',
        'delivery_status',
        'sender_info',
    ];

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class, 'shipment_id', 'id')
                    ->orderBy('event_date', 'desc');
    }

    public function latestTrackingEvent()
    {
        return $this->trackingEvents()->first();
    }

    public function rider()
    {
        return $this->belongsTo(\FluentShipment\App\Models\Rider::class, 'rider_id', 'id');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::getStatusLabels()[$this->current_status] ?? ucfirst($this->current_status);
    }

    public function getFormattedShippingCostAttribute(): string
    {
        if ( ! $this->shipping_cost) {
            return '$0.00';
        }

        $amount = $this->shipping_cost / 100; // Convert from cents

        return '$' . number_format($amount, 2);
    }

    public function getIsTrackableAttribute(): bool
    {
        return ! empty($this->tracking_number);
    }

    public function getDeliveryStatusAttribute(): string
    {
        if ($this->current_status === static::STATUS_DELIVERED) {
            return 'delivered';
        }

        if (in_array($this->current_status, [static::STATUS_FAILED, static::STATUS_CANCELLED, static::STATUS_RETURNED])) {
            return 'failed';
        }

        if (in_array(
            $this->current_status,
            [static::STATUS_SHIPPED, static::STATUS_IN_TRANSIT, static::STATUS_OUT_FOR_DELIVERY]
        )) {
            return 'in_transit';
        }

        return 'pending';
    }

    public function getSenderInfoAttribute(): ?array
    {
        return $this->meta['sender'] ?? null;
    }

    public static function getStatuses(): array
    {
        return [
            static::STATUS_PENDING,
            static::STATUS_PROCESSING,
            static::STATUS_SHIPPED,
            static::STATUS_IN_TRANSIT,
            static::STATUS_OUT_FOR_DELIVERY,
            static::STATUS_DELIVERED,
            static::STATUS_FAILED,
            static::STATUS_CANCELLED,
            static::STATUS_RETURNED,
            static::STATUS_EXCEPTION,
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            static::STATUS_PENDING          => 'Pending',
            static::STATUS_PROCESSING       => 'Processing',
            static::STATUS_SHIPPED          => 'Shipped',
            static::STATUS_IN_TRANSIT       => 'In Transit',
            static::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            static::STATUS_DELIVERED        => 'Delivered',
            static::STATUS_FAILED           => 'Failed',
            static::STATUS_CANCELLED        => 'Cancelled',
            static::STATUS_RETURNED         => 'Returned',
            static::STATUS_EXCEPTION        => 'Exception',
        ];
    }

    public static function getOrderSources(): array
    {
        return [
            static::SOURCE_FLUENT_CART,
            static::SOURCE_WOOCOMMERCE,
            static::SOURCE_MANUAL,
            static::SOURCE_API,
        ];
    }

    public function updateStatus(string $newStatus, array $eventData = []): bool
    {
        if ( ! in_array($newStatus, static::getStatuses())) {
            return false;
        }

        $oldStatus            = $this->current_status;
        $this->current_status = $newStatus;

        // Set specific timestamps based on status
        if ($newStatus === static::STATUS_SHIPPED && ! $this->shipped_at) {
            $this->shipped_at = DateTimeHelper::now();
        }

        if ($newStatus === static::STATUS_DELIVERED && ! $this->delivered_at) {
            $this->delivered_at = DateTimeHelper::now();
        }

        // Clear delivered_at if status changes from delivered
        if ($oldStatus === static::STATUS_DELIVERED && $newStatus !== static::STATUS_DELIVERED) {
            $this->delivered_at = null;
        }

        $saved = $this->save();

        if ($saved && $newStatus !== $oldStatus) {
            $this->createTrackingEvent($newStatus, $eventData);
        }

        return $saved;
    }

    public function createTrackingEvent(string $status, array $data = []): ShipmentTrackingEvent
    {
        return ShipmentTrackingEvent::create([
            'shipment_id'       => $this->id,
            'event_status'      => $status,
            'event_description' => $data['description'] ?? static::getStatusLabels()[$status] ?? $status,
            'event_location'    => $data['location'] ?? null,
            'event_date'        => $data['date'] ?? DateTimeHelper::now(),
            'carrier_data'      => $data['carrier_data'] ?? null,
            'is_milestone'      => $data['is_milestone'] ?? static::isMilestoneStatus($status),
        ]);
    }

    public static function isMilestoneStatus(string $status): bool
    {
        return in_array($status, [
            static::STATUS_SHIPPED,
            static::STATUS_OUT_FOR_DELIVERY,
            static::STATUS_DELIVERED,
            static::STATUS_FAILED,
            static::STATUS_CANCELLED,
            static::STATUS_RETURNED,
        ]);
    }

    public function getTrackingUrl(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }

        return null;
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeOfOrderSource($query, $source)
    {
        return $query->where('order_source', $source);
    }

    public function scopeDelivered($query)
    {
        return $query->where('current_status', static::STATUS_DELIVERED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('current_status', [static::STATUS_PENDING, static::STATUS_PROCESSING]);
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('current_status', [
            static::STATUS_SHIPPED,
            static::STATUS_IN_TRANSIT,
            static::STATUS_OUT_FOR_DELIVERY
        ]);
    }
}
