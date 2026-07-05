<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'is_active',
        'expires_at',
        'user_id',
        'brand_id',
        'category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function isExpired(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        return false;
    }

    public function isValidForAmount(float $amount, ?int $userId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->isExpired()) {
            return false;
        }
        if ($this->user_id && (int)$userId !== (int)$this->user_id) {
            return false;
        }
        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return false;
        }
        return true;
    }

    public function calculateDiscountForCart(array $cartItems): float
    {
        $qualifyingTotal = 0.00;
        
        foreach ($cartItems as $item) {
            $product = Product::find($item['id']);
            if (!$product) {
                continue;
            }
            
            // Check category restriction (including child subcategories recursively!)
            if ($this->category_id) {
                $category = Category::find($this->category_id);
                $allowedIds = $category ? array_merge([$category->id], $category->getAllDescendantIds()) : [$this->category_id];
                if (!in_array($product->category_id, $allowedIds)) {
                    continue;
                }
            }
            
            // Check brand restriction
            if ($this->brand_id && $product->brand_id != $this->brand_id) {
                continue;
            }
            
            $qualifyingTotal += $item['total'];
        }

        if ($qualifyingTotal <= 0) {
            return 0.00;
        }

        if ($this->type === 'percent') {
            return round(($qualifyingTotal * ($this->value / 100)), 2);
        }
        
        return min($this->value, $qualifyingTotal);
    }
}
