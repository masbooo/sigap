<?php

namespace App\Models;

class User extends SigapModel
{
    protected $table = 'user';

    protected $fillable = [
        'username',
        'password',
        'nik',
        'name',
        'gender',
        'address',
        'subdistrict_id',
        'district_id',
        'phone',
        'pic_path',
        'id_path',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'subdistrict_id' => 'integer',
        'district_id' => 'integer',
        'last_login' => 'datetime',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservasi::class, 'user_id');
    }

    public function resets()
    {
        return $this->hasMany(Reset::class, 'user_id');
    }

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(Kelurahan::class, 'subdistrict_id');
    }
}
