<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name', 'slug', 'sku', 'price', 'sale_price', 'stock', 'images',
            'category', 'brand', 'short_description', 'description', 'is_active', 'is_featured',
            'variant_name', 'variant_sku', 'variant_price', 'variant_stock', 'variant_images'
        ];
    }

    public function array(): array
    {
        $products = Product::with(['category', 'brand', 'variants'])->latest('id')->get();
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
            ];

            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $index => $variant) {
                    $variantImagesList = is_array($variant->images) ? implode(',', $variant->images) : '';
                    
                    if ($index === 0) {
                        $rows[] = array_merge($baseData, [
                            $variant->name,
                            $variant->sku,
                            $variant->price,
                            $variant->stock,
                            $variantImagesList
                        ]);
                    } else {
                        $rows[] = array_merge(array_fill(0, count($baseData), ''), [
                            $variant->name,
                            $variant->sku,
                            $variant->price,
                            $variant->stock,
                            $variantImagesList
                        ]);
                    }
                }
            } else {
                $rows[] = array_merge($baseData, ['', '', '', '', '']);
            }
        }

        return $rows;
    }
}
