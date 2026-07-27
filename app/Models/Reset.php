<?php

namespace App\Models;

class Reset extends SigapModel
{
    protected $table = 'reset';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'token',
        'expired_at',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
