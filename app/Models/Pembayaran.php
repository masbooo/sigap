<?php

namespace App\Models;

class Pembayaran extends SigapModel
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'reservation_id',
        'payment_method',
        'provider',
        'external_id',
        'payment_code',
        'qris_url',
        'amount',
        'status',
        'expired_at',
        'paid_at',
        'cancelled_at',
        'last_checked_at',
        'raw_response',
        'callback_payload',
        'callback_received_at',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'amount' => 'decimal:2',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'raw_response' => 'array',
        'callback_payload' => 'array',
        'callback_received_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservasi::class, 'reservation_id');
    }

    public function histories()
    {
        return $this->hasMany(ReservasiRiwayat::class, 'payment_id');
    }
}
