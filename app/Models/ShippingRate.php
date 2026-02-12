<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'zone',
        'price',
        'free_over',
        'pickup_only',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'free_over' => 'decimal:2',
        'pickup_only' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
}

