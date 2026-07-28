<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'locations',
        'shipping_charge',
        'free_shipping_threshold',
        'estimated_delivery_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'locations' => 'array',
        'shipping_charge' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
