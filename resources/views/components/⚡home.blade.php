<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Banner;
use App\Services\CartService;

new class extends Component
{
    public $categories = [];
    public $brands = [];
    public $featuredProducts = [];
    public $newArrivals = [];
    public array $banners = [];

    public function mount()
    {
        $this->categories = Category::where('is_active', true)
            ->select(['id', 'name', 'slug', 'image', 'description'])
            ->get();

        $this->brands = Brand::where('is_active', true)
            ->select(['id', 'name', 'slug', 'logo'])
            ->get();

        $this->featuredProducts = Product::with('brand')
            ->where('is_active', true)
            ->where('is_featured', true)
            ->select(['id', 'name', 'slug', 'price', 'sale_price', 'images', 'short_description', 'brand_id'])
            ->limit(4)
            ->get();

        // Fetch newest arrivals
        $this->newArrivals = Product::with('brand')
            ->where('is_active', true)
            ->select(['id', 'name', 'slug', 'price', 'sale_price', 'images', 'short_description', 'brand_id'])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        // Fetch active banners sorted by sort order
        $this->banners = Banner::where('is_active', true)
            ->select(['id', 'image_path', 'url'])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->toArray();
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
    <!-- Full-Width Responsive Hero Banner Auto-Slider -->
    <section class="w-full">
        @if(count($banners) > 0)
            <div x-data="{ 
                activeSlide: 0, 
                slides: {{ json_encode($banners) }},
                init() {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                    }, 5000); // auto-slide every 5 seconds
                }
            }" class="relative w-full aspect-[16/9] sm:h-[400px] md:h-[500px] lg:h-[600px] overflow-hidden bg-slate-100 group shadow-inner">
                <!-- Slides Wrapper -->
                <div class="h-full w-full relative">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div x-show="activeSlide === index" 
                             x-transition:enter="transition ease-out duration-1000 transform"
                             x-transition:enter-start="opacity-0 scale-102"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-800 transform"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-98"
                             class="absolute inset-0 w-full h-full">
                            <!-- Link target wrap -->
                            <template x-if="slide.url">
                                <a :href="slide.url" class="block w-full h-full">
                                    <img :src="slide.image_path" class="w-full h-full object-fill sm:object-cover select-none cursor-pointer" alt="Promo banner">
                                </a>
                            </template>
                            <template x-if="!slide.url">
                                <img :src="slide.image_path" class="w-full h-full object-fill sm:object-cover select-none" alt="Promo banner">
                            </template>
                        </div>
                    </template>
                </div>
                
                <!-- Navigation Dots -->
                <template x-if="slides.length > 1">
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-10">
                        <template x-for="(slide, index) in slides" :key="index">
                            <button @click="activeSlide = index" 
                                    :class="activeSlide === index ? 'bg-indigo-600 w-6' : 'bg-slate-400/60 hover:bg-slate-500 w-2'" 
                                    class="h-2 rounded-full transition-all duration-300 shadow-sm"></button>
                        </template>
                    </div>
                </template>

                <!-- Navigation Arrows -->
                <template x-if="slides.length > 1">
                    <div class="hidden md:block">
                        <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" 
                                class="absolute left-5 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white border border-slate-200/80 p-2.5 rounded-full opacity-0 group-hover:opacity-100 transition duration-300 shadow-md">
                            <svg class="h-4 w-4 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button @click="activeSlide = (activeSlide + 1) % slides.length" 
                                class="absolute right-5 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white border border-slate-200/80 p-2.5 rounded-full opacity-0 group-hover:opacity-100 transition duration-300 shadow-md">
                            <svg class="h-4 w-4 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        @else
            <!-- Clean Centered Hero Layout (Fallback when no banners are loaded) -->
            <div class="w-full py-20 text-center bg-gradient-to-tr from-slate-900 via-indigo-950 to-purple-950 text-white flex flex-col items-center justify-center space-y-4 px-4 shadow-inner">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-indigo-200">
                    Premium Hardware & Tech
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white max-w-2xl leading-tight">
                    Unleash the Future of Digital Shopping
                </h1>
                <p class="max-w-md mx-auto text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Welcome to Saffron Store! Please upload homepage banner slides in your Admin Panel > Home Settings block to start the promotion slider.
                </p>
                <div class="flex justify-center gap-4 pt-2">
                    <a href="/shop" class="rounded-xl bg-white text-slate-950 px-6 py-2.5 text-xs font-bold shadow hover:bg-slate-100 transition">
                        Shop Catalog
                    </a>
                </div>
            </div>
        @endif
    </section>

    <!-- New Arrivals Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">New Arrivals</h2>
                <p class="text-sm text-slate-500 mt-1">Fresh arrivals of our newest premium computer components.</p>
            </div>
            <a href="/shop?sort=latest" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 transition">
                View All &rarr;
            </a>
        </div>

        <div x-data="{
            scrollContainer: null,
            scrollInterval: null,
            init() {
                this.scrollContainer = this.$refs.newArrivalsSlider;
                this.scrollInterval = setInterval(() => {
                    if (window.innerWidth < 640 && this.scrollContainer) {
                        let maxScroll = this.scrollContainer.scrollWidth - this.scrollContainer.clientWidth;
                        if (this.scrollContainer.scrollLeft >= maxScroll - 10) {
                            this.scrollContainer.scrollTo({ left: 0, behavior: 'smooth' });
                        } else {
                            this.scrollContainer.scrollBy({ left: 234, behavior: 'smooth' });
                        }
                    }
                }, 4000);
            },
            destroy() {
                if (this.scrollInterval) clearInterval(this.scrollInterval);
            }
        }" class="relative w-full">
            <div x-ref="newArrivalsSlider" class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-4 sm:grid sm:grid-cols-2 md:grid-cols-4 sm:overflow-x-visible sm:pb-0 scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($newArrivals as $prod)
                    <div class="group relative bg-white border border-slate-200 rounded-xl sm:rounded-2xl overflow-hidden hover:border-indigo-500 hover:shadow-lg transition duration-300 flex flex-col h-full snap-start shrink-0 w-[210px] sm:w-auto">
                        <!-- Product Link Wrapper (wraps image and text details) -->
                        <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}" class="block flex-1 flex flex-col">
                            <!-- Image -->
                            <div class="aspect-square w-full relative overflow-hidden bg-slate-50 border-b border-slate-100">
                                <img src="{{ $prod->images[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop' }}" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                                @if($prod->sale_price)
                                    <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-rose-500 text-white text-[9px] sm:text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-full">
                                        Sale
                                    </span>
                                @endif
                            </div>
                            <!-- Details -->
                            <div class="p-3.5 sm:p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ $prod->brand->name ?? '' }}</span>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 mt-0.5 sm:mt-1 line-clamp-1 group-hover:text-indigo-650 transition">
                                        {{ $prod->name }}
                                    </h3>
                                    <p class="text-[11px] sm:text-xs text-slate-500 mt-1 line-clamp-2">{{ strip_tags($prod->short_description) }}</p>
                                </div>
                                <div class="mt-3 sm:mt-4">
                                    <div class="flex items-baseline flex-wrap gap-1.5 sm:gap-2 mb-3 sm:mb-4">
                                        @if($prod->sale_price)
                                            <span class="text-xs sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->sale_price) }}</span>
                                            <span class="text-[10px] sm:text-xs text-slate-400 line-through">₹{{ number_format($prod->price) }}</span>
                                        @else
                                            <span class="text-xs sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->price) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Add to Cart -->
                        <div class="px-3.5 pb-3.5 sm:px-5 sm:pb-5">
                            <button 
                                wire:click="addToCart({{ $prod->id }})"
                                class="w-full rounded-lg sm:rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2 sm:py-2.5 text-[10px] sm:text-xs font-bold text-white shadow transition duration-300 flex items-center justify-center gap-1 sm:gap-1.5"
                            >
                                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                @endforeach
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

    <!-- Why Choose Us Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="text-center max-w-3xl mx-auto mb-10 space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Why Choose Us</h2>
            <p class="text-xs sm:text-sm text-slate-500">We set the gold standard in premium hardware components and customer shopping experiences.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Advantage 1 -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-500 transition duration-300 group flex flex-col items-center text-center space-y-4">
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-650 group-hover:scale-105 transition duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Super Fast Delivery</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Free, insured express shipping on all flagship component orders over ₹50,000.</p>
            </div>

            <!-- Advantage 2 -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-500 transition duration-300 group flex flex-col items-center text-center space-y-4">
                <div class="p-3 bg-purple-50 rounded-xl text-purple-600 group-hover:scale-105 transition duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">100% Genuine Tech</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Direct manufacturer warranties, authentic batch validation, and certified stock quality.</p>
            </div>

            <!-- Advantage 3 -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-500 transition duration-300 group flex flex-col items-center text-center space-y-4">
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 group-hover:scale-105 transition duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Secure Payments</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Fully encrypted online payment checkout workflows powered directly by Razorpay.</p>
            </div>

            <!-- Advantage 4 -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-500 transition duration-300 group flex flex-col items-center text-center space-y-4">
                <div class="p-3 bg-rose-50 rounded-xl text-rose-600 group-hover:scale-105 transition duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Dedicated Support</h3>
                <p class="text-xs text-slate-500 leading-relaxed">24/7 technical customer support desk and seamless, hassle-free product exchanges.</p>
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

        <div x-data="{
            scrollContainer: null,
            scrollInterval: null,
            init() {
                this.scrollContainer = this.$refs.featuredSlider;
                this.scrollInterval = setInterval(() => {
                    if (window.innerWidth < 640 && this.scrollContainer) {
                        let maxScroll = this.scrollContainer.scrollWidth - this.scrollContainer.clientWidth;
                        if (this.scrollContainer.scrollLeft >= maxScroll - 10) {
                            this.scrollContainer.scrollTo({ left: 0, behavior: 'smooth' });
                        } else {
                            this.scrollContainer.scrollBy({ left: 234, behavior: 'smooth' });
                        }
                    }
                }, 4000);
            },
            destroy() {
                if (this.scrollInterval) clearInterval(this.scrollInterval);
            }
        }" class="relative w-full">
            <div x-ref="featuredSlider" class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-4 sm:grid sm:grid-cols-2 md:grid-cols-4 sm:overflow-x-visible sm:pb-0 scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($featuredProducts as $prod)
                    <div class="group relative bg-white border border-slate-200 rounded-xl sm:rounded-2xl overflow-hidden hover:border-indigo-500 hover:shadow-lg transition duration-300 flex flex-col h-full snap-start shrink-0 w-[210px] sm:w-auto">
                        <!-- Product Link Wrapper (wraps image and text details) -->
                        <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}" class="block flex-1 flex flex-col">
                            <!-- Image -->
                            <div class="aspect-square w-full relative overflow-hidden bg-slate-50 border-b border-slate-100">
                                <img src="{{ $prod->images[0] }}" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                                @if($prod->sale_price)
                                    <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-rose-500 text-white text-[9px] sm:text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-full">
                                        Sale
                                    </span>
                                @endif
                            </div>
                            <!-- Details -->
                            <div class="p-3.5 sm:p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ $prod->brand->name }}</span>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 mt-0.5 sm:mt-1 line-clamp-1 group-hover:text-indigo-650 transition">
                                        {{ $prod->name }}
                                    </h3>
                                    <p class="text-[11px] sm:text-xs text-slate-500 mt-1 line-clamp-2">{{ strip_tags($prod->short_description) }}</p>
                                </div>
                                <div class="mt-3 sm:mt-4">
                                    <div class="flex items-baseline flex-wrap gap-1.5 sm:gap-2 mb-3 sm:mb-4">
                                        @if($prod->sale_price)
                                            <span class="text-xs sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->sale_price) }}</span>
                                            <span class="text-[10px] sm:text-xs text-slate-400 line-through">₹{{ number_format($prod->price) }}</span>
                                        @else
                                            <span class="text-xs sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->price) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Add to Cart -->
                        <div class="px-3.5 pb-3.5 sm:px-5 sm:pb-5">
                            <button 
                                wire:click="addToCart({{ $prod->id }})"
                                class="w-full rounded-lg sm:rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2 sm:py-2.5 text-[10px] sm:text-xs font-bold text-white shadow transition duration-300 flex items-center justify-center gap-1 sm:gap-1.5"
                            >
                                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


</div>