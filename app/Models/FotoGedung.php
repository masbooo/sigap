<?php

namespace App\Models;

class FotoGedung extends SigapModel
{
    protected $table = 'foto_gedung';

    protected $fillable = [
        'building_id',
        'image_path',
        'is_thumbnail',
    ];

    protected $casts = [
        'building_id' => 'integer',
        'is_thumbnail' => 'boolean',
    ];

    public function building()
    {
        return $this->belongsTo(Gedung::class, 'building_id');
    }
}
