<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private $importedProductsCount = 0;
    private $importedVariantsCount = 0;

    public function collection(Collection $rows)
    {
        $lastProduct = null;

        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim($row['name']) : '';
            
            // If name is present, this is a product row (and potentially its first variant)
            if (!empty($name)) {
                $categorySlug = isset($row['category']) ? Str::slug(trim($row['category'])) : '';
                $brandSlug = isset($row['brand']) ? Str::slug(trim($row['brand'])) : '';

                $category = Category::where('slug', $categorySlug)->first();
                if (!$category && !empty($row['category'])) {
                    $category = Category::create([
                        'name' => trim($row['category']),
                        'slug' => $categorySlug,
                        'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop',
                    ]);
                }

                $brand = null;
                if (!empty($row['brand'])) {
                    $brand = Brand::where('slug', $brandSlug)->first();
                    if (!$brand) {
                        $brand = Brand::create([
                            'name' => trim($row['brand']),
                            'slug' => $brandSlug,
                            'logo' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=100&auto=format&fit=crop',
                        ]);
                    }
                }

                $slug = !empty($row['slug']) ? trim($row['slug']) : Str::slug($name);
                $sku = !empty($row['sku']) ? trim($row['sku']) : 'SKU-' . strtoupper(Str::random(8));
                
                $images = [];
                if (!empty($row['images'])) {
                    $images = array_map('trim', explode(',', $row['images']));
                } else {
                    $images = ['https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop'];
                }

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'sku' => $sku,
                        'price' => isset($row['price']) ? (float)$row['price'] : 0,
                        'sale_price' => !empty($row['sale_price']) ? (float)$row['sale_price'] : null,
                        'stock' => isset($row['stock']) ? (int)$row['stock'] : 0,
                        'images' => $images,
                        'short_description' => isset($row['short_description']) ? trim($row['short_description']) : null,
                        'description' => isset($row['description']) ? trim($row['description']) : null,
                        'category_id' => $category ? $category->id : null,
                        'brand_id' => $brand ? $brand->id : null,
                        'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
                        'is_featured' => isset($row['is_featured']) ? (bool)$row['is_featured'] : false,
                    ]
                );

                $lastProduct = $product;
                $this->importedProductsCount++;
            }

            // If we have a product context and variant info is provided, create the variant
            if ($lastProduct && !empty($row['variant_name'])) {
                $variantImages = [];
                if (!empty($row['variant_images'])) {
                    $variantImages = array_map('trim', explode(',', $row['variant_images']));
                }

                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $lastProduct->id,
                        'sku' => trim($row['variant_sku']) ?: ($lastProduct->sku . '-' . strtoupper(Str::random(4))),
                    ],
                    [
                        'name' => trim($row['variant_name']),
                        'price' => !empty($row['variant_price']) ? (float)$row['variant_price'] : null,
                        'stock' => isset($row['variant_stock']) ? (int)$row['variant_stock'] : 0,
                        'images' => $variantImages,
                        'is_active' => true,
                    ]
                );
                
                $this->importedVariantsCount++;
            }
        }
    }

    public function getImportedProductsCount(): int
    {
        return $this->importedProductsCount;
    }

    public function getImportedVariantsCount(): int
    {
        return $this->importedVariantsCount;
    }
}
