<?php

namespace App\Models;

class Admin extends SigapModel
{
    protected $table = 'admin';

    protected $fillable = [
        'username',
        'password',
        'name',
        'role_id',
        'district_id',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'district_id' => 'integer',
        'last_login' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }
}
