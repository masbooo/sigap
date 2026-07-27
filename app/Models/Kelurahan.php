<?php

namespace App\Models;

class Kelurahan extends SigapModel
{
    protected $table = 'kelurahan';

    public $timestamps = false;

    protected $fillable = [
        'district_id',
        'subdistrict',
    ];

    protected $casts = [
        'district_id' => 'integer',
    ];

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }
}
