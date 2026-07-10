<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class SeoService
{
    public const STORE_NAME = 'Saffron Store';

    private static ?Product $product = null;

    private static ?Category $category = null;

    public static function resolve(
        ?string $title = null,
        ?string $metaDescription = null,
        ?string $canonical = null,
        ?bool $noindex = null,
        ?string $ogType = null,
    ): array {
        $product = self::product();
        $category = self::category();

        $resolvedTitle = $title ?? self::defaultTitle($product, $category);
        $resolvedDescription = $metaDescription ?? self::defaultDescription($product, $category);
        $resolvedCanonical = $canonical ?? self::defaultCanonical($product, $category);
        $resolvedNoindex = $noindex ?? self::shouldNoindex();
        $resolvedOgType = $ogType ?? ($product ? 'product' : 'website');
        $resolvedImage = self::defaultImage($product);

        return [
            'title' => $resolvedTitle,
            'description' => Str::limit(strip_tags($resolvedDescription), 160, ''),
            'canonical' => $resolvedCanonical,
            'noindex' => $resolvedNoindex,
            'og_type' => $resolvedOgType,
            'image' => $resolvedImage,
            'structured_data' => self::structuredData($product, $category, $resolvedCanonical),
        ];
    }

    public static function product(): ?Product
    {
        if (self::$product !== null) {
            return self::$product;
        }

        if (! request()->routeIs('shop.detail')) {
            return null;
        }

        $slug = request()->route('slug');
        if (! $slug) {
            return null;
        }

        self::$product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['brand', 'category', 'reviews'])
            ->first();

        return self::$product;
    }

    public static function category(): ?Category
    {
        if (self::$category !== null) {
            return self::$category;
        }

        if (! request()->routeIs('categories.detail')) {
            return null;
        }

        $slug = request()->route('slug');
        if (! $slug) {
            return null;
        }

        self::$category = Category::query()
            ->with('parent')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        return self::$category;
    }

    private static function defaultTitle(?Product $product, ?Category $category): string
    {
        return match (true) {
            $product !== null => $product->name . ' - ' . self::STORE_NAME,
            $category !== null => $category->name . ' Categories - ' . self::STORE_NAME,
            request()->routeIs('home') => self::STORE_NAME . ' - Premium Online Shopping Hub',
            request()->routeIs('shop') => 'Shop Premium Products - ' . self::STORE_NAME,
            request()->routeIs('privacy-policy') => 'Privacy Policy - ' . self::STORE_NAME,
            request()->routeIs('terms-of-service') => 'Terms of Service - ' . self::STORE_NAME,
            request()->routeIs('shipping-policy') => 'Shipping Policy - ' . self::STORE_NAME,
            request()->routeIs('payment-methods') => 'Payment Methods - ' . self::STORE_NAME,
            request()->routeIs('checkout') => 'Checkout - ' . self::STORE_NAME,
            request()->routeIs('orders*') => 'My Orders - ' . self::STORE_NAME,
            request()->routeIs('login') => 'Login - ' . self::STORE_NAME,
            request()->routeIs('register') => 'Register - ' . self::STORE_NAME,
            request()->routeIs('verify-email') => 'Verify Email - ' . self::STORE_NAME,
            default => self::STORE_NAME . ' - Premium Online Shopping Hub',
        };
    }

    private static function defaultDescription(?Product $product, ?Category $category): string
    {
        return match (true) {
            $product !== null => $product->short_description
                ? strip_tags($product->short_description)
                : 'Buy ' . $product->name . ' at ' . self::STORE_NAME . '. Check reviews, stock levels, and specifications.',
            $category !== null => $category->description
                ? strip_tags($category->description)
                : 'Browse ' . $category->name . ' categories and subcategories at ' . self::STORE_NAME . '.',
            request()->routeIs('shop') => 'Browse premium products, hot deals, and exclusive catalogs with quick shipping at ' . self::STORE_NAME . '.',
            request()->routeIs('privacy-policy') => 'Read how ' . self::STORE_NAME . ' collects, uses, and protects your personal information.',
            request()->routeIs('terms-of-service') => 'Review the terms and conditions for shopping at ' . self::STORE_NAME . '.',
            request()->routeIs('shipping-policy') => 'Learn about shipping timelines, delivery areas, and policies at ' . self::STORE_NAME . '.',
            request()->routeIs('payment-methods') => 'See available payment options and secure checkout methods at ' . self::STORE_NAME . '.',
            request()->routeIs('checkout') => 'Secure checkout portal for ' . self::STORE_NAME . ' purchases.',
            request()->routeIs('orders*') => 'Track your placed orders, invoices, and shipment status at ' . self::STORE_NAME . '.',
            default => 'Discover premium products at ' . self::STORE_NAME . '. Fast deliveries, secure payments, and expert customer service.',
        };
    }

    private static function defaultCanonical(?Product $product, ?Category $category): string
    {
        if ($product !== null) {
            return route('shop.detail', ['slug' => $product->slug]);
        }

        if ($category !== null) {
            return route('categories.detail', ['slug' => $category->slug]);
        }

        if (request()->routeIs('shop') && request()->query()) {
            return route('shop');
        }

        return url()->current();
    }

    private static function shouldNoindex(): bool
    {
        if (request()->routeIs([
            'admin.*',
            'admin.login',
            'login',
            'register',
            'verify-email',
            'checkout',
            'orders',
            'orders.detail',
            'order-success',
        ])) {
            return true;
        }

        if (request()->routeIs('shop') && request()->query()) {
            return true;
        }

        return false;
    }

    private static function defaultImage(?Product $product): ?string
    {
        if ($product !== null && is_array($product->images) && count($product->images) > 0) {
            $firstImg = $product->images[0];

            return str_starts_with($firstImg, 'http') ? $firstImg : asset($firstImg);
        }

        return HomeSettingsService::bannerImageUrl();
    }

    private static function structuredData(?Product $product, ?Category $category, string $canonical): array
    {
        $data = [
            self::organizationSchema(),
            self::websiteSchema(),
        ];

        if ($product !== null) {
            $crumbs = [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Shop', 'url' => route('shop')],
            ];

            if ($product->category) {
                $crumbs[] = [
                    'name' => $product->category->name,
                    'url' => route('shop', ['category' => $product->category->slug]),
                ];
            }

            $crumbs[] = ['name' => $product->name, 'url' => $canonical];

            $data[] = self::productSchema($product, $canonical);
            $data[] = self::breadcrumbSchema($crumbs);
        }

        if ($category !== null) {
            $crumbs = [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Shop', 'url' => route('shop')],
            ];

            if ($category->parent) {
                $crumbs[] = [
                    'name' => $category->parent->name,
                    'url' => route('categories.detail', ['slug' => $category->parent->slug]),
                ];
            }

            $crumbs[] = ['name' => $category->name, 'url' => $canonical];
            $data[] = self::breadcrumbSchema($crumbs);
        }

        return array_values(array_filter($data));
    }

    private static function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::STORE_NAME,
            'url' => url('/'),
        ];
    }

    private static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::STORE_NAME,
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('shop') . '?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    private static function productSchema(Product $product, string $canonical): array
    {
        $price = $product->sale_price ?? $product->price;
        $images = collect(is_array($product->images) ? $product->images : [])
            ->map(fn ($img) => str_starts_with($img, 'http') ? $img : asset($img))
            ->values()
            ->all();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->short_description ?: $product->description ?: $product->name),
            'sku' => $product->sku,
            'url' => $canonical,
            'image' => $images,
            'brand' => $product->brand ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => 'INR',
                'price' => (float) $price,
                'availability' => $product->stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($product->reviews->isNotEmpty()) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($product->reviews->avg('rating'), 1),
                'reviewCount' => $product->reviews->count(),
            ];
        }

        return array_filter($schema);
    }

    private static function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(function ($item, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ];
            })->all(),
        ];
    }
}
