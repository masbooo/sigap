<?php

namespace App\Models;

class Menu extends SigapModel
{
    protected $table = 'menu';

    public $timestamps = false;

    protected $fillable = [
        'heading_menu',
        'label_parent',
        'label_child',
        'link_href',
        'icon_menu',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_peran', 'menu_id', 'peran_id')
            ->withPivot('is_allowed')
            ->withTimestamps();
    }
}
