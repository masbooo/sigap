<?php

namespace App\Models;

class GedungUmkm extends SigapModel
{
    protected $table = 'gedung_umkm';

    public const UPDATED_AT = null;

    protected $fillable = [
        'gedung_id',
        'umkm_id',
    ];

    protected $casts = [
        'gedung_id' => 'integer',
        'umkm_id' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Gedung::class, 'gedung_id');
    }

    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }
}
