<?php

namespace App\Models;

class Umkm extends SigapModel
{
    protected $table = 'umkm';

    protected $fillable = [
        'product_id',
        'district_id',
        'subdistrict_id',
        'category',
        'umkm_name',
        'owner',
        'address',
        'phone',
        'description',
        'pic_path',
        'rating_avg',
        'rating_count',
        'review_count',
        'status',
        'dedupe_key',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'district_id' => 'integer',
        'subdistrict_id' => 'integer',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'review_count' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Produk::class, 'product_id');
    }

    public function district()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(Kelurahan::class, 'subdistrict_id');
    }

    public function buildings()
    {
        return $this->belongsToMany(Gedung::class, 'gedung_umkm', 'umkm_id', 'gedung_id');
    }

    public function ratings()
    {
        return $this->hasMany(UmkmRating::class, 'umkm_id');
    }
}
