<?php

namespace App\Models;

class RatingUlasan extends SigapModel
{
    protected $table = 'rating_ulasan';

    protected $fillable = [
        'user_id',
        'reservation_id',
        'target_type',
        'target_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'reservation_id' => 'integer',
        'target_id' => 'integer',
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservasi::class, 'reservation_id');
    }
}
