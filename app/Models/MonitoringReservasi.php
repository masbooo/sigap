<?php

namespace App\Models;

class MonitoringReservasi extends SigapModel
{
    protected $table = 'v_monitoring_reservasi';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'district_id' => 'integer',
        'building_id' => 'integer',
        'event_id' => 'integer',
        'total_price' => 'decimal:2',
        'status_id' => 'integer',
        'sort_order' => 'integer',
        'is_final' => 'boolean',
        'active_payment_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'active_payment_expired_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
