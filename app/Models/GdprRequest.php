<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GdprRequest extends Model
{
    protected $fillable = [
        'type',
        'shop_domain',
        'customer_id',
        'payload',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
