<?php

namespace App\Models;

class Gedung extends SigapModel
{
    protected $table = 'gedung';

    protected $fillable = [
        'building_name',
        'description',
        'address',
        'district_id',
        'subdistrict_id',
        'building_area',
        'capacity',
        'session_price',
        'perhour_price',
        'rating_avg',
        'rating_count',
        'review_count',
        'status',
    ];

    protected $casts = [
        'district_id' => 'integer',
        'subdistrict_id' => 'integer',
        'building_area' => 'integer',
        'capacity' => 'integer',
        'session_price' => 'decimal:2',
        'perhour_price' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'review_count' => 'integer',
    ];

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(Kelurahan::class, 'subdistrict_id');
    }

    public function photos()
    {
        return $this->hasMany(FotoGedung::class, 'building_id');
    }

    public function umkms()
    {
        return $this->belongsToMany(Umkm::class, 'gedung_umkm', 'gedung_id', 'umkm_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservasi::class, 'building_id');
    }

    public function ratings()
    {
        return $this->hasMany(GedungRating::class, 'building_id');
    }
}
