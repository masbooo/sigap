<?php

namespace App\Models;

class StatusReservasi extends SigapModel
{
    protected $table = 'status_reservasi';

    protected $fillable = [
        'code',
        'name',
        'description',
        'actor',
        'sort_order',
        'is_final',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_final' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservasi::class, 'status_id');
    }

    public function historiesFrom()
    {
        return $this->hasMany(ReservasiRiwayat::class, 'from_status_id');
    }

    public function historiesTo()
    {
        return $this->hasMany(ReservasiRiwayat::class, 'to_status_id');
    }
}
