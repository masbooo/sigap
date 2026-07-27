<?php

namespace App\Models;

class ReservasiRiwayat extends SigapModel
{
    protected $table = 'reservasi_riwayat';

    protected $fillable = [
        'reservation_id',
        'from_status_id',
        'to_status_id',
        'actor_type',
        'actor_id',
        'payment_id',
        'action',
        'note',
        'created_at',
    ];

    public const UPDATED_AT = null;

    protected $casts = [
        'reservation_id' => 'integer',
        'from_status_id' => 'integer',
        'to_status_id' => 'integer',
        'actor_id' => 'integer',
        'payment_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservasi::class, 'reservation_id');
    }

    public function fromStatus()
    {
        return $this->belongsTo(StatusReservasi::class, 'from_status_id');
    }

    public function toStatus()
    {
        return $this->belongsTo(StatusReservasi::class, 'to_status_id');
    }

    public function payment()
    {
        return $this->belongsTo(Pembayaran::class, 'payment_id');
    }
}
