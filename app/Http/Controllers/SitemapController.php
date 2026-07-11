<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $staticPages = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('shop'), 'priority' => '0.9'],
            ['loc' => route('privacy-policy'), 'priority' => '0.3'],
            ['loc' => route('terms-of-service'), 'priority' => '0.3'],
            ['loc' => route('shipping-policy'), 'priority' => '0.4'],
            ['loc' => route('payment-methods'), 'priority' => '0.4'],
            ['loc' => route('refund-policy'), 'priority' => '0.4'],
        ];

        $products = Product::query()
            ->where('is_active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($product) => [
                'loc' => route('shop.detail', ['slug' => $product->slug]),
                'lastmod' => $product->updated_at?->toAtomString(),
                'priority' => '0.8',
            ]);

        $categories = Category::query()
            ->where('is_active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($category) => [
                'loc' => route('categories.detail', ['slug' => $category->slug]),
                'lastmod' => $category->updated_at?->toAtomString(),
                'priority' => '0.7',
            ]);

        return response()
            ->view('sitemap', [
                'staticPages' => $staticPages,
                'products' => $products,
                'categories' => $categories,
            ])
            ->header('Content-Type', 'application/xml');
    }
}
