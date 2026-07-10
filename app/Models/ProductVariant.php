<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'value',
        'sku',
        'price',
        'sale_price',
        'stock',
        'images',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'images' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colorHex(): ?string
    {
        $value = trim((string) $this->value);

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return $value;
        }

        if (preg_match('/^[0-9A-Fa-f]{6}$/', $value)) {
            return '#' . $value;
        }

        return null;
    }

    public function displayValue(): string
    {
        if ($this->product?->variant_type === Product::VARIANT_TYPE_COLOR) {
            return $this->name ?: ($this->colorHex() ?? 'Color');
        }

        return $this->value ?: $this->name;
    }
}
