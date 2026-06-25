<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Review;
use App\Services\CartService;

new class extends Component
{
    public Product $product;
    public int $quantity = 1;
    
    // Review form
    public int $rating = 5;
    public string $comment = '';

    public $relatedProducts = [];

    public function mount(string $slug)
    {
        $this->product = Product::where('slug', $slug)->with(['reviews.user', 'brand', 'category'])->firstOrFail();
        $this->relatedProducts = Product::where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();
    }

    public function incrementQuantity()
    {
        if ($this->quantity < $this->product->stock) {
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
        if ($this->product->stock <= 0) {
            return;
        }
        
        CartService::add($this->product->id, $this->quantity);
        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart-drawer');
    }

    public function submitReview()
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
        ]);

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $this->product->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
        ]);

        $this->comment = '';
        $this->rating = 5;
        $this->product->load('reviews.user');
        
        session()->flash('review_success', 'Thank you for your review!');
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
    <!-- Breadcrumbs -->
    <nav class="text-sm text-slate-500">
        <a href="/" class="hover:text-indigo-600 transition">Home</a> &nbsp;/&nbsp;
        <a href="/shop" class="hover:text-indigo-600 transition">Shop</a> &nbsp;/&nbsp;
        <a href="/shop?category={{ $product->category->slug }}" class="hover:text-indigo-600 transition">{{ $product->category->name }}</a> &nbsp;/&nbsp;
        <span class="text-slate-800 font-semibold">{{ $product->name }}</span>
    </nav>

    <!-- Product Intro -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Gallery -->
        <div class="space-y-4">
            <div class="aspect-square bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            </div>
            
            <!-- Sub-images thumbnails if any -->
            @if(count($product->images) > 1)
                <div class="grid grid-cols-4 gap-4">
                    @foreach($product->images as $img)
                        <div class="aspect-square bg-white border border-slate-200 rounded-xl overflow-hidden cursor-pointer hover:border-indigo-600 transition">
                            <img src="{{ $img }}" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Purchase Panel -->
        <div class="flex flex-col justify-between py-2">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">{{ $product->brand->name }}</span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1 mb-3">{{ $product->name }}</h1>
                
                <!-- Ratings display -->
                <div class="flex items-center gap-2 mb-6">
                    <div class="flex text-amber-400">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                    <span class="text-xs text-slate-500 font-semibold">({{ $product->reviews->count() }} customer review)</span>
                </div>

                <!-- Pricing -->
                <div class="flex items-baseline gap-3 mb-6">
                    @if($product->sale_price)
                        <span class="text-3xl font-extrabold text-slate-900">₹{{ number_format($product->sale_price) }}</span>
                        <span class="text-sm text-slate-400 line-through">₹{{ number_format($product->price) }}</span>
                    @else
                        <span class="text-3xl font-extrabold text-slate-900">₹{{ number_format($product->price) }}</span>
                    @endif
                </div>

                <p class="text-slate-600 text-sm leading-relaxed mb-6">{{ $product->short_description }}</p>

                <!-- Stock indicators -->
                <div class="mb-6">
                    @if($product->stock > 0)
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                            In Stock ({{ $product->stock }} items)
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/10">
                            Out of Stock
                        </span>
                    @endif
                </div>
            </div>

            <!-- Cart Control -->
            @if($product->stock > 0)
                <div class="space-y-4 pt-6 border-t border-slate-200">
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
                            class="flex-1 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 h-12 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition flex items-center justify-center gap-2"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Add to Shopping Cart
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Long Description -->
    <section class="border-t border-slate-200 pt-8">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Product Overview</h2>
        <div class="prose prose-indigo text-slate-650 text-sm leading-relaxed">
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
                
                @if (session()->has('review_success'))
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
                        {{ session('review_success') }}
                    </div>
                @endif

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
                        You must be <a href="/login" class="text-indigo-650 hover:underline">signed in</a> to write a review.
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
                    <a href="/shop/{{ $prod->slug }}" class="group bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-indigo-600 hover:shadow-md transition duration-300 flex flex-col h-full shadow-sm">
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