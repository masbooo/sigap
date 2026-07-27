<?php

namespace App\Models;

class Acara extends SigapModel
{
    protected $table = 'acara';

    public $timestamps = false;

    protected $fillable = [
        'event_name',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservasi::class, 'event_id');
    }
}
