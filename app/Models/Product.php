<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const VARIANT_TYPE_COLOR = 'color';
    public const VARIANT_TYPE_SIZE = 'size';
    public const VARIANT_TYPE_WEIGHT = 'weight';
    public const VARIANT_TYPE_OTHER = 'other';

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'sale_price',
        'stock',
        'images',
        'video_path',
        'is_active',
        'is_featured',
        'category_id',
        'brand_id',
        'variant_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public static function variantTypes(): array
    {
        return [
            self::VARIANT_TYPE_COLOR => 'Color',
            self::VARIANT_TYPE_SIZE => 'Size',
            self::VARIANT_TYPE_WEIGHT => 'Weight',
            self::VARIANT_TYPE_OTHER => 'Other',
        ];
    }

    public function variantTypeLabel(): string
    {
        return self::variantTypes()[$this->variant_type] ?? 'Option';
    }
}
