<?php

namespace App\Models;

class Role extends SigapModel
{
    protected $table = 'peran';

    public $timestamps = false;

    protected $fillable = [
        'role_name',
        'description',
    ];

    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_peran', 'peran_id', 'menu_id')
            ->withPivot('is_allowed')
            ->withTimestamps();
    }
}
