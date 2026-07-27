<?php

namespace App\Models;

class RiwayatReservasiView extends SigapModel
{
    protected $table = 'v_riwayat_reservasi';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'reservation_id' => 'integer',
        'actor_id' => 'integer',
        'payment_id' => 'integer',
        'created_at' => 'datetime',
    ];
}
