<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BeautyBerryCatalogueSeeder extends Seeder
{
    private const DEFAULT_STOCK = 50;

    private const PLACEHOLDER_IMAGE = 'https://placehold.co/800x800/f8fafc/64748b?text=Beauty+Berry';

    public function run(): void
    {
        $jsonPath = storage_path('app/beauty_berry_products.json');

        if (! File::exists($jsonPath)) {
            $this->command?->error('Missing catalogue JSON: storage/app/beauty_berry_products.json');
            $this->command?->line('Run the PDF parser first, then re-seed.');

            return;
        }

        $products = json_decode(File::get($jsonPath), true);

        if (! is_array($products) || count($products) === 0) {
            $this->command?->error('Catalogue JSON is empty or invalid.');

            return;
        }

        DB::disableQueryLog();

        $brand = Brand::firstOrCreate(
            ['slug' => 'beauty-berry'],
            [
                'name' => 'Beauty Berry',
                'logo' => null,
                'is_active' => true,
            ]
        );

        $parentCategory = Category::firstOrCreate(
            ['slug' => 'makeup'],
            [
                'name' => 'Makeup',
                'description' => 'Beauty Berry cosmetics and makeup products.',
                'image' => self::PLACEHOLDER_IMAGE,
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        $created = 0;
        $updated = 0;
        $variantsCreated = 0;

        foreach ($products as $row) {
            $itemNo = trim((string) ($row['item_no'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($itemNo === '' || $name === '') {
                continue;
            }

            $mrp = (float) ($row['mrp'] ?? 0);
            $ws = (float) ($row['ws_price'] ?? 0);
            $price = $this->resolveRetailPrice($mrp, $ws, (string) ($row['notes'] ?? ''));

            if ($price <= 0) {
                $this->command?->warn("Skipping {$itemNo}: invalid price");
                continue;
            }

            $categoryName = trim((string) ($row['category'] ?? 'Makeup')) ?: 'Makeup';
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'description' => $categoryName . ' products from Beauty Berry.',
                    'image' => self::PLACEHOLDER_IMAGE,
                    'is_active' => true,
                    'parent_id' => $parentCategory->id,
                ]
            );

            $variants = is_array($row['variants'] ?? null) ? $row['variants'] : [];
            $variantType = $this->resolveVariantType($row['variant_type'] ?? null, $variants);

            $slugBase = Str::slug($name . '-' . $itemNo);
            $slug = $slugBase;
            $suffix = 1;
            while (
                Product::where('slug', $slug)
                    ->where('sku', '!=', $itemNo)
                    ->exists()
            ) {
                $slug = $slugBase . '-' . $suffix;
                $suffix++;
            }

            $notes = trim((string) ($row['notes'] ?? ''));
            $short = trim((string) ($row['short_description'] ?? ''));
            if ($short === '') {
                $short = $name . ' by Beauty Berry.';
            }
            if ($notes !== '' && ! str_contains($short, $notes)) {
                $short .= ' ' . $notes;
            }

            $description = '<p>' . e($name) . ' from Beauty Berry Cosmetics.</p>'
                . '<p>Item No: <strong>' . e($itemNo) . '</strong></p>'
                . '<p>MRP: ₹' . number_format($mrp, 2) . ' (Incl. GST)</p>';

            if ($ws > 0) {
                $description .= '<p>Wholesale reference: ₹' . number_format($ws, 2) . '</p>';
            }
            if ($notes !== '') {
                $description .= '<p>' . e($notes) . '</p>';
            }

            $productStock = count($variants) > 0 ? 0 : self::DEFAULT_STOCK;

            $product = Product::updateOrCreate(
                ['sku' => $itemNo],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'short_description' => Str::limit(strip_tags($short), 480, ''),
                    'price' => $price,
                    'sale_price' => null,
                    'stock' => $productStock,
                    'images' => [self::PLACEHOLDER_IMAGE],
                    'is_active' => true,
                    'is_featured' => false,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'variant_type' => $variantType,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
                // Keep existing slug if product already existed with different slug
                if ($product->slug !== $slug && ! Product::where('slug', $product->slug)->where('id', '!=', $product->id)->exists()) {
                    // leave current slug
                }
            }

            // Replace variants for this product to match catalogue
            ProductVariant::where('product_id', $product->id)->delete();

            foreach ($variants as $index => $variant) {
                $variantName = trim((string) ($variant['name'] ?? ''));
                if ($variantName === '') {
                    continue;
                }

                $shadeCode = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $variantSku = $itemNo . '-' . Str::upper(Str::slug($variantName, ''));
                if (strlen($variantSku) > 90) {
                    $variantSku = $itemNo . '-' . $shadeCode;
                }

                // Ensure unique SKU
                $baseSku = $variantSku;
                $i = 1;
                while (ProductVariant::where('sku', $variantSku)->exists()) {
                    $variantSku = $baseSku . '-' . $i;
                    $i++;
                }

                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variantName,
                    'value' => $variant['value'] ?? null,
                    'sku' => $variantSku,
                    'price' => null,
                    'sale_price' => null,
                    'stock' => self::DEFAULT_STOCK,
                    'images' => [],
                    'is_active' => true,
                ]);
                $variantsCreated++;
            }

            if (count($variants) > 0) {
                $product->update([
                    'stock' => ProductVariant::where('product_id', $product->id)->sum('stock'),
                ]);
            }
        }

        $this->command?->info("Beauty Berry import complete.");
        $this->command?->info("Products created: {$created}");
        $this->command?->info("Products updated: {$updated}");
        $this->command?->info("Variants created: {$variantsCreated}");
    }

    private function resolveRetailPrice(float $mrp, float $ws, string $notes): float
    {
        // Catalogue sometimes lists wholesale per dozen higher than single MRP
        if ($mrp > 0 && (str_contains(Str::upper($notes), 'DOZ') || ($ws > 0 && $mrp < $ws && $ws >= $mrp * 2))) {
            return $mrp;
        }

        if ($mrp > 0) {
            return $mrp;
        }

        return $ws > 0 ? $ws : 0;
    }

    private function resolveVariantType(?string $type, array $variants): string
    {
        if (count($variants) === 0) {
            return Product::VARIANT_TYPE_OTHER;
        }

        $type = Str::lower(trim((string) $type));

        return match ($type) {
            'color', 'shade', 'shades' => Product::VARIANT_TYPE_COLOR,
            'size' => Product::VARIANT_TYPE_SIZE,
            'weight' => Product::VARIANT_TYPE_WEIGHT,
            default => Product::VARIANT_TYPE_COLOR,
        };
    }
}
