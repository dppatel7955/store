<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LargeProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Disable query logging to prevent memory exhaustion
        DB::disableQueryLog();

        // 2. Define and Seed Categories
        $categoriesData = [
            'Electronics' => 'Latest tech gadgets and electronic devices.',
            'Fashion & Apparel' => 'Stylish clothing and apparel for everyone.',
            'Home & Kitchen' => 'High-quality home goods and kitchenware.',
            'Books & Stationery' => 'Read, write, and create with premium stationery.',
            'Beauty & Personal Care' => 'Cosmetics, skincare, and personal hygiene.',
            'Sports & Outdoors' => 'Gear up for sports, hiking, and outdoor activities.',
            'Toys & Games' => 'Fun games, puzzles, and toys for all ages.',
            'Automotive' => 'Car accessories, tools, and maintenance gear.',
            'Groceries' => 'Daily essential groceries and food items.',
            'Office Supplies' => 'Printers, paper, pens, and office furniture.'
        ];

        $this->command->info('Setting up categories...');
        $categoryIds = [];
        foreach ($categoriesData as $name => $desc) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop',
                    'is_active' => true,
                ]
            );
            $categoryIds[] = $category->id;
        }

        // 3. Define and Seed Brands
        $brandsData = ['Apple', 'Samsung', 'Dell', 'Lenovo', 'Sony', 'Nike', 'Adidas', 'Logitech', 'HP', 'Anker'];
        $this->command->info('Setting up brands...');
        $brandIds = [];
        foreach ($brandsData as $name) {
            $brand = Brand::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'logo' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?q=80&w=200&auto=format&fit=crop',
                    'is_active' => true,
                ]
            );
            $brandIds[] = $brand->id;
        }

        // 4. Seed Products in Chunks
        $this->command->info('Generating and inserting 50,000 products...');
        $now = Carbon::now();
        $products = [];
        $chunkSize = 250;
        $totalProducts = 50000;

        $adjectives = ['Ultra', 'Smart', 'Eco', 'Pro', 'Max', 'Mini', 'Classic', 'Premium', 'Elite', 'Wireless', 'Portable', 'Digital', 'Advanced', 'Active', 'Cyber', 'Neo', 'Quantum', 'Optima', 'Vortex', 'Apex'];
        $nouns = ['Gadget', 'Device', 'Hub', 'Pack', 'Kit', 'Case', 'Drive', 'Link', 'Adapter', 'Screen', 'Band', 'Gear', 'Core', 'Module', 'Pod', 'Bud', 'Grid', 'Key', 'Watch', 'Lens'];

        for ($i = 1; $i <= $totalProducts; $i++) {
            $adj = $adjectives[$i % count($adjectives)];
            $noun = $nouns[($i + 5) % count($nouns)];
            $name = "{$adj} {$noun} #" . (10000 + $i);
            $slug = Str::slug($name) . '-' . $i;
            
            $price = round(rand(10, 5000) * 9.99, 2);
            $salePrice = rand(1, 10) === 1 ? round($price * 0.8, 2) : null;
            $stock = rand(0, 500);
            $images = json_encode(['https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop']);
            
            $products[] = [
                'name' => $name,
                'slug' => $slug,
                'description' => "<p>Discover the incredible features of {$name}. Designed for high performance and durability, this product meets all your daily needs with style and efficiency.</p>",
                'short_description' => "High-quality {$name} with premium design and advanced features.",
                'price' => $price,
                'sale_price' => $salePrice,
                'stock' => $stock,
                'images' => $images,
                'is_active' => true,
                'is_featured' => rand(1, 20) === 1,
                'category_id' => $categoryIds[$i % count($categoryIds)],
                'brand_id' => $brandIds[$i % count($brandIds)],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($products) >= $chunkSize) {
                DB::table('products')->insert($products);
                $products = [];
            }
        }

        if (count($products) > 0) {
            DB::table('products')->insert($products);
        }

        $this->command->info('Successfully seeded 50,000 products!');
    }
}
