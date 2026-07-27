<?php

namespace App\Models;

class MenuRole extends SigapModel
{
    protected $table = 'menu_peran';

    protected $fillable = [
        'peran_id',
        'menu_id',
        'is_allowed',
    ];

    protected $casts = [
        'peran_id' => 'integer',
        'menu_id' => 'integer',
        'is_allowed' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'peran_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
