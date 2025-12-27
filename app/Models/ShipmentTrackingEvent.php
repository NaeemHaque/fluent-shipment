<?php

namespace FluentShipment\App\Models;

use FluentShipment\App\Models\Model;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\Framework\Database\Orm\Relations\BelongsTo;

class ShipmentTrackingEvent extends Model
{
    protected $table = 'fluent_shipment_tracking_events';

    protected $primaryKey = 'id';

    public $timestamps = ['created_at'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'shipment_id',
        'event_status',
        'event_description',
        'event_location',
        'event_date',
        'carrier_data',
        'is_milestone',
    ];

    protected $casts = [
        'shipment_id' => 'integer',
        'event_date' => 'datetime',
        'created_at' => 'datetime',
        'carrier_data' => 'array',
        'is_milestone' => 'boolean',
    ];

    protected $appends = [
        'formatted_date',
        'status_label',
    ];

    /**
     * Get the shipment that owns this tracking event
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', 'id');
    }

    /**
     * Get formatted date attribute
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->event_date->format('M j, Y g:i A');
    }

    /**
     * Get status label attribute
     */
    public function getStatusLabelAttribute(): string
    {
        return Shipment::getStatusLabels()[$this->event_status] ?? ucfirst($this->event_status);
    }

    /**
     * Scope to get milestone events only
     */
    public function scopeMilestones($query)
    {
        return $query->where('is_milestone', true);
    }

    /**
     * Scope to get events for a specific shipment
     */
    public function scopeForShipment($query, $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    /**
     * Scope to get recent events
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('event_date', '>=', DateTimeHelper::daysAgo($days));
    }
}