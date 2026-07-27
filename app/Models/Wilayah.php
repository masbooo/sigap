<?php

namespace App\Models;

class Wilayah extends SigapModel
{
    protected $table = 'wilayah';

    public $timestamps = false;

    protected $fillable = [
        'region',
    ];
}
