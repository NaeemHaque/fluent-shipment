<?php

namespace FluentShipment\App\Models;

use FluentShipment\App\Models\Model;

class Shipment extends Model
{
    protected $table = 'fluent_shipments';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'order_id',
        'order_source',
        'tracking_number',
        'current_status',
        'delivery_address',
        'estimated_delivery',
        'delivered_at',
        'customer_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'estimated_delivery' => 'date',
        'delivered_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
