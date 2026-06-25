<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Services\CartService;

new class extends Component
{
    public $categories = [];
    public $brands = [];
    public $featuredProducts = [];

    public function mount()
    {
        $this->categories = Category::where('is_active', true)->get();
        $this->brands = Brand::where('is_active', true)->get();
        $this->featuredProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->limit(4)
            ->get();
    }

    public function addToCart(int $productId)
    {
        CartService::add($productId, 1);
        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart-drawer');
    }
};
?>

<div class="space-y-16">
    <!-- Hero Banner -->
    <section class="relative overflow-hidden bg-white border-b border-slate-200 py-24 sm:py-32 shadow-sm">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-650 ring-1 ring-inset ring-indigo-200/50 mb-6">
                Premium Hardware & Tech
            </span>
            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900 mb-6">
                Unleash the Future of <br>
                <span class="bg-gradient-to-r from-indigo-650 via-purple-650 to-pink-655 bg-clip-text text-transparent">
                    Digital Shopping
                </span>
            </h1>
            <p class="max-w-xl mx-auto text-lg text-slate-650 mb-10 leading-relaxed">
                Explore handpicked flagship smartphones, laptops, smartwatches, and accessories with lightning-fast checkout.
            </p>
            <div class="flex justify-center gap-4">
                <a href="/shop" class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-650 px-6 py-3 text-sm font-bold text-white shadow-md hover:from-indigo-700 hover:to-purple-760 transition">
                    Shop Catalog
                </a>
                <a href="#featured" class="rounded-xl bg-white border border-slate-250 px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    Featured Deals
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Grid -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center md:text-left mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Browse Categories</h2>
            <p class="text-sm text-slate-500 mt-1">Find products in our curated technological categories.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($categories as $cat)
                <a href="/shop?category={{ $cat->slug }}" class="group relative block overflow-hidden rounded-2xl bg-white border border-slate-200 p-6 text-center hover:border-indigo-500 hover:shadow-md transition duration-300">
                    <div class="h-24 w-24 mx-auto mb-4 overflow-hidden rounded-full border border-slate-100 group-hover:scale-105 transition duration-300">
                        <img src="{{ $cat->image }}" alt="{{ $cat->name }}" class="h-full w-full object-cover">
                    </div>
                    <h3 class="text-base font-bold text-slate-800 group-hover:text-indigo-600 transition">{{ $cat->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $cat->description }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Brands Section -->
    <section class="bg-white border-y border-slate-200 py-12 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-center md:justify-between gap-8 md:gap-4">
                @foreach($brands as $brand)
                    <a href="/shop?brand={{ $brand->slug }}" class="flex items-center gap-3 grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition duration-300">
                        <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" class="h-8 w-8 object-contain rounded-full border border-slate-200 p-0.5 bg-white">
                        <span class="text-lg font-bold text-slate-700">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="featured" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Featured Products</h2>
                <p class="text-sm text-slate-500 mt-1">Handpicked hot picks recommended just for you.</p>
            </div>
            <a href="/shop" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 transition">
                View All &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($featuredProducts as $prod)
                <div class="group relative bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-indigo-500 hover:shadow-lg transition duration-300 flex flex-col h-full">
                    <!-- Image -->
                    <div class="aspect-square relative overflow-hidden bg-slate-50 border-b border-slate-100">
                        <img src="{{ $prod->images[0] }}" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                        @if($prod->sale_price)
                            <span class="absolute top-3 left-3 bg-rose-500 text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded-full">
                                Sale
                            </span>
                        @endif
                    </div>
                    <!-- Details -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ $prod->brand->name }}</span>
                            <h3 class="text-sm font-bold text-slate-800 mt-1 line-clamp-1">
                                {{ $prod->name }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $prod->short_description }}</p>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-baseline gap-2 mb-4">
                                @if($prod->sale_price)
                                    <span class="text-base font-extrabold text-slate-900">₹{{ number_format($prod->sale_price) }}</span>
                                    <span class="text-xs text-slate-400 line-through">₹{{ number_format($prod->price) }}</span>
                                @else
                                    <span class="text-base font-extrabold text-slate-900">₹{{ number_format($prod->price) }}</span>
                                @endif
                            </div>
                            
                            <button 
                                wire:click="addToCart({{ $prod->id }})"
                                class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 text-xs font-bold text-white shadow transition duration-300 flex items-center justify-center gap-1.5"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>