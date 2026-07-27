<?php

namespace App\Models;

class Produk extends SigapModel
{
    protected $table = 'produk';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'status',
    ];

    public function umkms()
    {
        return $this->hasMany(Umkm::class, 'product_id');
    }
}
