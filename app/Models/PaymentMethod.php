<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'handler',
        'gateway_key',
        'gateway_secret',
        'description',
        'instructions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'gateway_key' => 'encrypted',
        'gateway_secret' => 'encrypted',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
