<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
        ]);

        $customer = User::create([
            'name' => 'John Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);

        // 2. Categories
        $categoriesData = [
            [
                'name' => 'Mobile Phones',
                'description' => 'Latest flagship smartphones and budget-friendly devices.',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=600&auto=format&fit=crop',
            ],
            [
                'name' => 'Laptops',
                'description' => 'Powerful laptops for gaming, business, and study.',
                'image' => 'https://images.unsplash.com/photo-1496181130204-7552cc145cdb?q=80&w=600&auto=format&fit=crop',
            ],
            [
                'name' => 'Accessories',
                'description' => 'Cases, chargers, headphones, and other essential gear.',
                'image' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?q=80&w=600&auto=format&fit=crop',
            ],
            [
                'name' => 'Smart Watches',
                'description' => 'Track your fitness and stay connected on the go.',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop',
            ]
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
                'image' => $cat['image'],
                'is_active' => true,
            ]);
        }

        // 3. Brands
        $brandsData = [
            ['name' => 'Apple', 'logo' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?q=80&w=200&auto=format&fit=crop'],
            ['name' => 'Samsung', 'logo' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?q=80&w=200&auto=format&fit=crop'],
            ['name' => 'Dell', 'logo' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?q=80&w=200&auto=format&fit=crop'],
            ['name' => 'Lenovo', 'logo' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?q=80&w=200&auto=format&fit=crop'],
        ];

        $brands = [];
        foreach ($brandsData as $brand) {
            $brands[$brand['name']] = Brand::create([
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']),
                'logo' => $brand['logo'],
                'is_active' => true,
            ]);
        }

        // 4. Products
        $productsData = [
            [
                'name' => 'iPhone 15 Pro Max',
                'description' => '<p>The iPhone 15 Pro Max features a strong and light aerospace-grade titanium design. Powered by the A17 Pro chip for next-level mobile gaming and performance. Includes a versatile 48MP camera system with 5x optical zoom.</p>',
                'short_description' => 'Titanium design, A17 Pro chip, 5x Telephoto camera.',
                'price' => 139999.00,
                'sale_price' => 129999.00,
                'stock' => 15,
                'images' => ['https://images.unsplash.com/photo-1695048133142-1a20484d2569?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => true,
                'category' => 'Mobile Phones',
                'brand' => 'Apple',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => '<p>Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display. Features revolutionary Galaxy AI tools to search, translate, and capture photos like never before.</p>',
                'short_description' => 'Titanium frame, Galaxy AI, 200MP camera, S Pen included.',
                'price' => 124999.00,
                'sale_price' => 119999.00,
                'stock' => 20,
                'images' => ['https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => true,
                'category' => 'Mobile Phones',
                'brand' => 'Samsung',
            ],
            [
                'name' => 'Dell XPS 15',
                'description' => '<p>The Dell XPS 15 features a stunning 15.6-inch display, a powerful Intel Core i7 processor, 16GB of DDR5 RAM, and a spacious 1TB SSD. Built with premium materials including carbon fiber and aluminum for durability and design elegance.</p>',
                'short_description' => 'Intel Core i7, 16GB RAM, 1TB SSD, 15.6" InfinityEdge display.',
                'price' => 179999.00,
                'sale_price' => null,
                'stock' => 8,
                'images' => ['https://images.unsplash.com/photo-1593642632823-8f785ba67e45?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => true,
                'category' => 'Laptops',
                'brand' => 'Dell',
            ],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'description' => '<p>The ultimate business laptop. ThinkPad X1 Carbon combines thin, light elegance with legendary durability and high performance. Powered by Intel Evo platforms, it offers long-lasting battery life and a premium keyboard.</p>',
                'short_description' => 'Intel Evo Platform, Carbon Fiber chassis, Legendary Keyboard.',
                'price' => 154999.00,
                'sale_price' => null,
                'stock' => 10,
                'images' => ['https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => false,
                'category' => 'Laptops',
                'brand' => 'Lenovo',
            ],
            [
                'name' => 'Apple Watch Ultra 2',
                'description' => '<p>The most rugged and capable Apple Watch. Designed for outdoor adventures and supercharged workouts with a lightweight titanium case, extra-long battery life, and the brightest display ever on an Apple Watch.</p>',
                'short_description' => '49mm titanium case, Dual-frequency GPS, Up to 36 hours battery life.',
                'price' => 89999.00,
                'sale_price' => 84999.00,
                'stock' => 25,
                'images' => ['https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => true,
                'category' => 'Smart Watches',
                'brand' => 'Apple',
            ],
            [
                'name' => 'Samsung Galaxy Watch 6',
                'description' => '<p>Track your health and wellness goals with the Samsung Galaxy Watch 6. Features sleep coaching, personalized heart rate zones, and a sleek layout with a 20% larger display and thinner bezel.</p>',
                'short_description' => 'Advanced Sleep Coaching, BIA Sensor, Personalized HR Zones.',
                'price' => 34999.00,
                'sale_price' => 29999.00,
                'stock' => 30,
                'images' => ['https://images.unsplash.com/photo-1579586337278-3befd40fd17a?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => false,
                'category' => 'Smart Watches',
                'brand' => 'Samsung',
            ],
            [
                'name' => 'Apple AirPods Pro 2',
                'description' => '<p>AirPods Pro feature up to two times more Active Noise Cancellation, plus Adaptive Audio and Transparency mode. Custom-engineered driver works with the H2 chip to deliver crisp highs and deep bass.</p>',
                'short_description' => 'Active Noise Cancellation, Adaptive Audio, MagSafe Charging Case.',
                'price' => 24999.00,
                'sale_price' => null,
                'stock' => 50,
                'images' => ['https://images.unsplash.com/photo-1588449668365-d15e397f6787?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => false,
                'category' => 'Accessories',
                'brand' => 'Apple',
            ],
            [
                'name' => 'Samsung Galaxy Buds 2 Pro',
                'description' => '<p>Immerse yourself in high-fidelity sound with 24-bit Hi-Fi audio. Intelligent Active Noise Cancellation blocks even the softest sounds, while the ergonomic layout offers all-day comfort.</p>',
                'short_description' => '24-bit Hi-Fi Audio, Intelligent ANC, Ergonomic fit.',
                'price' => 17999.00,
                'sale_price' => 14999.00,
                'stock' => 40,
                'images' => ['https://images.unsplash.com/photo-1608156639585-b3a032ef9689?q=80&w=600&auto=format&fit=crop'],
                'is_active' => true,
                'is_featured' => false,
                'category' => 'Accessories',
                'brand' => 'Samsung',
            ],
        ];

        foreach ($productsData as $prod) {
            $product = Product::create([
                'name' => $prod['name'],
                'slug' => Str::slug($prod['name']),
                'description' => $prod['description'],
                'short_description' => $prod['short_description'],
                'price' => $prod['price'],
                'sale_price' => $prod['sale_price'],
                'stock' => $prod['stock'],
                'images' => $prod['images'],
                'is_active' => $prod['is_active'],
                'is_featured' => $prod['is_featured'],
                'category_id' => $categories[$prod['category']]->id,
                'brand_id' => $brands[$prod['brand']]->id,
            ]);

            // Add a mock review for each product
            Review::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => "Amazing quality! Exactly as described. Highly recommended.",
            ]);
        }
    }
}
