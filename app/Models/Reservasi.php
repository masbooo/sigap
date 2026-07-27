<?php

namespace App\Models;

class Reservasi extends SigapModel
{
    protected $table = 'reservasi';

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'order_id',
        'user_id',
        'district_id',
        'building_id',
        'start_date',
        'end_date',
        'event_id',
        'est_person',
        'umkm_id',
        'start_time',
        'end_time',
        'hour_count',
        'total_price',
        'status_id',
        'id_path',
        'form_path',
        'umkm_path',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'district_id' => 'integer',
        'building_id' => 'integer',
        'event_id' => 'integer',
        'est_person' => 'integer',
        'umkm_id' => 'integer',
        'hour_count' => 'integer',
        'total_price' => 'decimal:2',
        'status_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function building()
    {
        return $this->belongsTo(Gedung::class, 'building_id');
    }

    public function event()
    {
        return $this->belongsTo(Acara::class, 'event_id');
    }

    public function status()
    {
        return $this->belongsTo(StatusReservasi::class, 'status_id');
    }

    public function payments()
    {
        return $this->hasMany(Pembayaran::class, 'reservation_id');
    }

    public function histories()
    {
        return $this->hasMany(ReservasiRiwayat::class, 'reservation_id');
    }

    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }
}
