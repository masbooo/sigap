<?php

namespace App\Models;

class UmkmRating extends SigapModel
{
    protected $table = 'umkm_rating';

    protected $fillable = [
        'umkm_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'umkm_id' => 'integer',
        'rating' => 'integer',
    ];

    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }
}
