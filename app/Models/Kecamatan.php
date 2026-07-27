<?php

namespace App\Models;

class Kecamatan extends SigapModel
{
    protected $table = 'kecamatan';

    public $timestamps = false;

    protected $fillable = [
        'district',
        'region',
        'address',
        'phone',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function villages()
    {
        return $this->hasMany(Kelurahan::class, 'district_id');
    }

    public function buildings()
    {
        return $this->hasMany(Gedung::class, 'district_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'district_id');
    }
}
