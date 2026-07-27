<?php

namespace App\Models;

class GedungRating extends SigapModel
{
    protected $table = 'gedung_rating';

    protected $fillable = [
        'building_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'building_id' => 'integer',
        'rating' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Gedung::class, 'building_id');
    }
}
