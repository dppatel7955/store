<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Review;
use App\Services\CartService;

new class extends Component
{
    public Product $product;
    public int $quantity = 1;
    public ?int $selectedVariantId = null;
    
    // Review form
    public int $rating = 5;
    public string $comment = '';

    public $relatedProducts = [];

    public function mount(string $slug)
    {
        $this->product = Product::where('slug', $slug)
            ->with(['reviews.user', 'brand', 'category', 'variants' => function($q) {
                $q->where('is_active', true);
            }])
            ->firstOrFail();

        if ($this->product->variants->isNotEmpty()) {
            $this->selectedVariantId = $this->product->variants->first()->id;
        }
            
        $this->relatedProducts = Product::where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();
    }

    public function incrementQuantity()
    {
        $maxStock = $this->product->stock;
        if ($this->selectedVariantId) {
            $variant = $this->product->variants->firstWhere('id', $this->selectedVariantId);
            if ($variant) {
                $maxStock = $variant->stock;
            }
        }

        if ($this->quantity < $maxStock) {
            $this->quantity++;
        }
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart()
    {
        $stock = $this->product->stock;
        $variant = null;

        if ($this->selectedVariantId) {
            $variant = $this->product->variants->firstWhere('id', $this->selectedVariantId);
            if ($variant) {
                $stock = $variant->stock;
            }
        }

        if ($stock <= 0) {
            $this->dispatch('swal', title: 'Out of Stock!', text: 'Sorry, this item/variant is currently out of stock.', icon: 'error');
            return;
        }

        if ($this->quantity > $stock) {
            $this->dispatch('swal', title: 'Insufficient Stock!', text: "Only {$stock} units available.", icon: 'warning');
            return;
        }
        
        CartService::add($this->product->id, $this->quantity, $this->selectedVariantId);
        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart-drawer');
        
        $displayName = $this->product->name;
        if ($variant) {
            $displayName .= " ({$variant->name})";
        }
        $this->dispatch('swal', title: 'Added to Cart!', text: "'{$displayName}' has been added to your shopping cart.", icon: 'success');
    }

    public function submitReview()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
        ]);

        Review::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $this->product->id,
            ],
            [
                'rating' => $this->rating,
                'comment' => $this->comment,
            ]
        );

        $this->comment = '';
        $this->rating = 5;
        $this->product->load([
            'brand',
            'category',
            'reviews.user',
        ]);
        
        $this->dispatch('swal', title: 'Review Submitted!', text: 'Thank you for your valuable feedback!', icon: 'success');
    }
    public function getAverageRatingProperty()
    {
        return round($this->product->reviews()->avg('rating'), 1);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
    <!-- Breadcrumbs -->
    <nav class="text-sm text-slate-500">
        <a href="/" class="hover:text-indigo-600 transition">Home</a> &nbsp;/&nbsp;
        <a href="/shop" class="hover:text-indigo-600 transition">Shop</a> &nbsp;/&nbsp;
        <a href="{{ route('categories.detail', ['slug' => $product->category->slug]) }}" class="hover:text-indigo-600 transition">{{ $product->category->name }}</a> &nbsp;/&nbsp;
        <span class="text-slate-800 font-semibold">{{ $product->name }}</span>
    </nav>

    <!-- Product Intro -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12" x-data="{
        productImages: {{ json_encode($product->images) }},
        activeImage: '{{ $product->images[0] ?? '' }}',
        activeImagesList: {{ json_encode($product->images) }},
        selectedVariantId: @entangle('selectedVariantId'),
        variants: {{ json_encode($product->variants) }},
        selectedVariantPrice: null,
        selectedVariantSalePrice: null,
        selectedVariantStock: {{ $product->stock }},
        selectedVariantSku: '{{ $product->sku }}',
        lightboxOpen: false,
        showVideo: false,
        videoPath: '{{ $product->video_path ?? '' }}',
        init() {
            if (this.selectedVariantId) {
                this.selectVariant(this.selectedVariantId);
            }
        },
        selectVariant(variantId) {
            this.selectedVariantId = variantId;
            let variant = this.variants.find(v => v.id == variantId);
            if (variant) {
                this.selectedVariantPrice = variant.price;
                this.selectedVariantSalePrice = variant.sale_price;
                this.selectedVariantStock = variant.stock;
                this.selectedVariantSku = variant.sku || '{{ $product->sku }}';
                
                if (variant.images && variant.images.length > 0) {
                    this.activeImagesList = variant.images;
                    this.activeImage = variant.images[0];
                    this.showVideo = false;
                } else {
                    this.activeImagesList = this.productImages;
                    this.activeImage = this.productImages[0] ?? '';
                }
            } else {
                this.selectedVariantPrice = null;
                this.selectedVariantSalePrice = null;
                this.selectedVariantStock = {{ $product->stock }};
                this.selectedVariantSku = '{{ $product->sku }}';
                this.activeImagesList = this.productImages;
                this.activeImage = this.productImages[0] ?? '';
            }
        }
    }">
        <!-- Gallery -->
        <div class="space-y-4">
            <div class="aspect-square bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm relative"
                 :class="!showVideo ? 'cursor-zoom-in' : ''"
                 x-data="{ zoom: false, x: 50, y: 50 }"
                 @click="if(!showVideo) lightboxOpen = true"
                 @mouseenter="if(!showVideo) zoom = true"
                 @mouseleave="zoom = false; x = 50; y = 50"
                 @mousemove="
                     const rect = $el.getBoundingClientRect();
                     x = (($event.clientX - rect.left) / rect.width) * 100;
                     y = (($event.clientY - rect.top) / rect.height) * 100;
                 "
            >
                <template x-if="!showVideo">
                    <img :src="activeImage" alt="{{ $product->name }}" 
                         class="h-full w-full object-cover transition-transform duration-75 ease-out origin-center"
                         :style="zoom ? `transform: scale(2.2); transform-origin: ${x}% ${y}%;` : 'transform: scale(1); transform-origin: center;'"
                    >
                </template>
                <template x-if="showVideo">
                    <video src="{{ $product->video_path }}" controls autoplay class="h-full w-full object-contain bg-black"></video>
                </template>
            </div>
            
            <!-- Sub-images thumbnails if any -->
            <div class="flex flex-wrap gap-3">
                <template x-for="(img, idx) in activeImagesList" :key="idx">
                    <div 
                        @click="activeImage = img; showVideo = false"
                        :class="(!showVideo && activeImage === img) ? 'border-indigo-650 ring-2 ring-indigo-500/20' : 'border-slate-200'"
                        class="h-14 w-14 bg-white border rounded-xl overflow-hidden cursor-pointer hover:border-indigo-650 transition duration-150"
                    >
                        <img :src="img" alt="Product Image Thumbnail" class="h-full w-full object-cover">
                    </div>
                </template>

                @if($product->video_path)
                    <div 
                        @click="showVideo = true; activeImage = ''"
                        :class="showVideo ? 'border-indigo-650 ring-2 ring-indigo-500/20' : 'border-slate-200'"
                        class="h-14 w-14 bg-slate-50 border rounded-xl overflow-hidden cursor-pointer hover:border-indigo-650 transition duration-150 flex flex-col items-center justify-center relative"
                    >
                        <svg class="h-5 w-5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[8px] font-extrabold text-indigo-700 mt-0.5">Video</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Lightbox Modal -->
        <div x-show="lightboxOpen" 
             x-transition:enter="transition ease-out duration-350"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-55 flex flex-col items-center justify-center bg-slate-950/95 p-4 md:p-10"
             style="display: none;"
             @keydown.escape.window="lightboxOpen = false"
        >
            <!-- Close button -->
            <button @click="lightboxOpen = false" class="absolute top-4 right-4 md:top-8 md:right-8 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-3 rounded-full transition z-55 shadow-lg">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="max-w-4xl w-full flex flex-col items-center justify-center space-y-6">
                <!-- Large Image -->
                <div class="w-full max-h-[70vh] flex items-center justify-center overflow-hidden rounded-2xl relative select-none">
                    <img :src="activeImage" alt="Zoomed view" class="max-w-full max-h-[70vh] object-contain rounded-xl shadow-2xl">
                </div>

                <!-- Thumbnail Navigator inside Lightbox -->
                <div class="flex justify-center gap-3 overflow-x-auto max-w-full py-2 px-4" x-show="activeImagesList.length > 1">
                    <template x-for="(img, idx) in activeImagesList" :key="idx">
                        <div 
                            @click="activeImage = img"
                            :class="activeImage === img ? 'border-indigo-500 ring-2 ring-indigo-500/50' : 'border-white/20'"
                            class="h-14 w-14 bg-slate-900 border rounded-xl overflow-hidden cursor-pointer hover:border-indigo-450 transition flex-shrink-0"
                        >
                            <img :src="img" alt="Thumbnail" class="h-full w-full object-cover">
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Purchase Panel -->
        <div class="flex flex-col justify-between py-2">
            <div>
                {{-- <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">{{ $product->brand->name }}</span> --}}
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1 mb-3">{{ $product->name }}</h1>
                
                <!-- Ratings display -->
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <svg
                            class="h-5 w-5 {{ $i <= round($this->averageRating) ? 'text-amber-400' : 'text-slate-300' }}"
                            fill="currentColor"
                            viewBox="0 0 20 20">

                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor

                    <span class="ml-2 text-sm font-semibold text-slate-800">
                        {{ number_format($this->averageRating, 1) }}
                    </span>

                    <span class="text-sm text-slate-500">
                        ({{ $product->reviews->count() }} Reviews)
                    </span>
                </div>

                <!-- Pricing -->
                <div class="flex items-baseline gap-3 mb-6">
                    <template x-if="selectedVariantPrice !== null">
                        <div class="flex items-baseline gap-3 flex-wrap">
                            <template x-if="selectedVariantSalePrice">
                                <div class="flex items-baseline gap-3 flex-wrap">
                                    <span class="text-3xl font-extrabold text-slate-900" x-text="'₹' + Number(selectedVariantSalePrice).toLocaleString()"></span>
                                    <span class="text-sm text-slate-400 line-through" x-text="'₹' + Number(selectedVariantPrice).toLocaleString()"></span>
                                    <span class="bg-rose-500 text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shadow-sm" x-text="Math.round(100 - (Number(selectedVariantSalePrice) / Number(selectedVariantPrice) * 100)) + '% OFF'"></span>
                                </div>
                            </template>
                            <template x-if="!selectedVariantSalePrice">
                                <span class="text-3xl font-extrabold text-slate-900" x-text="'₹' + Number(selectedVariantPrice).toLocaleString()"></span>
                            </template>
                        </div>
                    </template>
                    <template x-if="selectedVariantPrice === null">
                        <div class="flex items-baseline gap-3 flex-wrap">
                            @if($product->sale_price)
                                <span class="text-3xl font-extrabold text-slate-900">₹{{ number_format($product->sale_price) }}</span>
                                <span class="text-sm text-slate-400 line-through">₹{{ number_format($product->price) }}</span>
                                <span class="bg-rose-500 text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shadow-sm">
                                    {{ round(100 - ($product->sale_price / $product->price * 100)) }}% OFF
                                </span>
                            @else
                                <span class="text-3xl font-extrabold text-slate-900">₹{{ number_format($product->price) }}</span>
                            @endif
                        </div>
                    </template>
                </div>

                <div class="text-slate-600 text-sm leading-relaxed mb-6 trix-content">{!! $product->short_description !!}</div>
                
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">

                    <h3 class="mb-4 text-sm font-bold text-slate-900">
                        Product Information
                    </h3>

                    <div class="grid grid-cols-2 gap-5">

                        <!-- Brand -->
                        @isset($product->brand?->name)
                            <div>
                                <p class="text-xs uppercase tracking-wider text-slate-400">
                                    Brand
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $product->brand?->name ?? 'N/A' }}
                                </p>
                            </div>
                        @endisset

                        <!-- Category -->
                        @isset($product->category?->name)
                            <div>
                                <p class="text-xs uppercase tracking-wider text-slate-400">
                                    Category
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $product->category?->name ?? 'N/A' }}
                                </p>
                            </div>
                        @endisset

                        <!-- SKU -->
                        @isset($product->sku)
                            <div>
                                <p class="text-xs uppercase tracking-wider text-slate-400">
                                    SKU
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="selectedVariantSku">
                                    {{ $product->sku }}
                                </p>
                            </div>
                        @endisset

                        <!-- Stock -->
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-400">
                                Availability
                            </p>

                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-sm font-semibold text-emerald-600" x-show="selectedVariantStock > 0" x-text="'In Stock (' + selectedVariantStock + ')'"></span>
                                <span class="text-sm font-semibold text-red-600" x-show="selectedVariantStock <= 0" style="display: none;">Out of Stock</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variant Selectors -->
                @if($product->variants->count() > 0)
                    <div class="mt-6 space-y-3">
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">Select Options</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->variants as $var)
                                <button 
                                    type="button"
                                    @click="selectVariant({{ $var->id }})"
                                    :class="selectedVariantId === {{ $var->id }} ? 'border-indigo-650 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-350'"
                                    class="px-4 py-2.5 text-xs font-semibold border rounded-xl shadow-sm transition duration-150"
                                >
                                    {{ $var->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-3">

                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                        <div class="rounded-lg bg-emerald-100 p-2">
                            🚚
                        </div>

                        <div>
                            <p class="text-sm font-semibold">Free Delivery</p>
                            <p class="text-xs text-slate-500">Fast Shipping</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                        <div class="rounded-lg bg-indigo-100 p-2">
                            🛡
                        </div>

                        <div>
                            <p class="text-sm font-semibold">Secure Payment</p>
                            <p class="text-xs text-slate-500">100% Safe Checkout</p>
                        </div>
                    </div>

                </div>

                <!-- Cart Control -->
                <div class="space-y-4 pt-6 border-t border-slate-200 mt-6" x-show="selectedVariantStock > 0">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center border border-slate-200 bg-slate-50 rounded-xl overflow-hidden h-12">
                            <button wire:click="decrementQuantity" class="px-4 text-slate-500 hover:text-slate-900 transition">
                                -
                            </button>
                            <span class="px-2 font-bold text-slate-800 text-sm w-8 text-center">{{ $quantity }}</span>
                            <button wire:click="incrementQuantity" class="px-4 text-slate-500 hover:text-slate-900 transition">
                                +
                            </button>
                        </div>

                        <button 
                            wire:click="addToCart"
                            wire:loading.attr="disabled"
                            class="flex-1 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 h-12 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition flex items-center justify-center gap-2"
                        >
                            <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Add to Cart
                            </span>
                            <span wire:loading wire:target="addToCart" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Adding...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Long Description -->
    <section class="border-t border-slate-200 pt-8">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Product Overview</h2>
        <div class="trix-content text-slate-650 text-sm leading-relaxed">
            {!! $product->description !!}
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="border-t border-slate-200 pt-8 grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Review Listing -->
        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-bold text-slate-900 mb-2">Customer Reviews ({{ $product->reviews->count() }})</h2>
            
            @if($product->reviews->count() > 0)
                <div class="space-y-4">
                    @foreach($product->reviews as $rev)
                        <div class="bg-slate-50/60 border border-slate-200 p-5 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-800">{{ $rev->user->name }}</span>
                                <span class="text-xs text-slate-400">{{ $rev->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex text-amber-400">
                                @for($i = 1; $i <= $rev->rating; $i++)
                                    <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-sm text-slate-650 leading-relaxed">{{ $rev->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-450 italic">No reviews yet for this product. Be the first to share your thoughts!</p>
            @endif
        </div>

        <!-- Add Review form -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sticky top-24 space-y-4 shadow-sm">
                <h3 class="font-bold text-slate-900 border-b border-slate-200 pb-3">Share Your Experience</h3>
                


                @auth
                    <form wire:submit="submitReview" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Rating</label>
                            <select wire:model="rating" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600">
                                <option value="5">5 Stars - Excellent</option>
                                <option value="4">4 Stars - Good</option>
                                <option value="3">3 Stars - Average</option>
                                <option value="2">2 Stars - Poor</option>
                                <option value="1">1 Star - Terrible</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Comment</label>
                            <textarea 
                                wire:model="comment" 
                                rows="4" 
                                placeholder="Write your review details here..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600"
                            ></textarea>
                            @error('comment') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 py-2.5 text-xs font-bold text-white shadow-sm transition">
                            Submit Review
                        </button>
                    </form>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed text-center py-6">
                        You must be <a href="{{ route('login') }}" class="text-indigo-650 hover:underline">signed in</a> to write a review.
                    </p>
                @endif
            </div>
        </div>
    </section>

    <!-- Related Products -->
    @if(count($relatedProducts) > 0)
        <section class="border-t border-slate-200 pt-12">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-6">You May Also Like</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($relatedProducts as $prod)
                    <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}" class="group bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-indigo-600 hover:shadow-md transition duration-300 flex flex-col h-full shadow-sm">
                        <div class="aspect-square relative overflow-hidden bg-slate-50">
                            <img src="{{ $prod->images[0] }}" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-550">
                        </div>
                        <div class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <span class="text-[9px] font-extrabold uppercase text-indigo-600">{{ $prod->brand->name }}</span>
                                <h3 class="text-xs font-bold text-slate-800 mt-0.5 line-clamp-1 group-hover:text-indigo-600 transition">{{ $prod->name }}</h3>
                            </div>
                            <div class="text-sm font-bold text-slate-900 mt-2">
                                ₹{{ number_format($prod->sale_price ?? $prod->price) }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>