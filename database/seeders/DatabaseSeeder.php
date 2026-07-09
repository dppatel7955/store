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
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'dppatel7955@gmail.com',
            'password' => bcrypt('admin123'),
        ]);
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
