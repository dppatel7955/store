<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromArray, WithHeadings
{
    protected $search;
    protected $categoryId;
    protected $brandId;

    public function __construct($search = '', $categoryId = '', $brandId = '')
    {
        $this->search = $search;
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
    }

    public function headings(): array
    {
        return [
            'name', 'slug', 'sku', 'price', 'sale_price', 'stock', 'images',
            'category', 'brand', 'short_description', 'description', 'is_active', 'is_featured',
            'variant_type', 'variant_name', 'variant_sku', 'variant_price', 'variant_sale_price',
            'variant_stock', 'variant_value', 'variant_images',
        ];
    }

    public function array(): array
    {
        $products = Product::query()
            ->with(['category', 'brand', 'variants'])
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryId, function ($query) {
                $query->where('category_id', $this->categoryId);
            })
            ->when($this->brandId, function ($query) {
                $query->where('brand_id', $this->brandId);
            })
            ->latest('id')
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $categoryName = $product->category?->name ?? '';
            $brandName = $product->brand?->name ?? '';
            $imagesList = is_array($product->images) ? implode(',', $product->images) : '';

            $baseData = [
                $product->name,
                $product->slug,
                $product->sku,
                $product->price,
                $product->sale_price,
                $product->stock,
                $imagesList,
                $categoryName,
                $brandName,
                $product->short_description,
                $product->description,
                $product->is_active ? 1 : 0,
                $product->is_featured ? 1 : 0,
                $product->variant_type ?? '',
            ];

            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $index => $variant) {
                    $variantImagesList = is_array($variant->images) ? implode(',', $variant->images) : '';
                    $variantCols = [
                        $variant->name,
                        $variant->sku,
                        $variant->price,
                        $variant->sale_price,
                        $variant->stock,
                        $variant->value,
                        $variantImagesList,
                    ];

                    if ($index === 0) {
                        $rows[] = array_merge($baseData, $variantCols);
                    } else {
                        $rows[] = array_merge(array_fill(0, count($baseData), ''), $variantCols);
                    }
                }
            } else {
                $rows[] = array_merge($baseData, ['', '', '', '', '', '', '']);
            }
        }

        return $rows;
    }
}
