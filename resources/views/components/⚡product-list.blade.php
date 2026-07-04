<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Services\CartService;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;
    // Filters
    public string $search = '';
    public array $selectedCategories = [];
    public array $selectedBrands = [];
    public float $minPrice = 0;
    public float $maxPrice = 250000;
    public string $sortBy = 'default';

    public $categoriesList = [];
    public $brandsList = [];
    


    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategories' => ['except' => []],
        'selectedBrands' => ['except' => []],
        'sortBy' => ['except' => 'default'],
    ];

    public function mount()
    {
        $this->categoriesList = Category::with('children')->whereNull('parent_id')->where('is_active', true)->get();
        $this->brandsList = Brand::where('is_active', true)->get();

        if (request()->has('search') && !empty(request('search'))) {
            $this->search = request('search');
        }
        if (request()->has('category')) {
            $category = Category::where('slug', request('category'))->first();
            if ($category) {
                $this->selectedCategories[] = $category->id;
            }
        }
        if (request()->has('brand')) {
            $brand = Brand::where('slug', request('brand'))->first();
            if ($brand) {
                $this->selectedBrands[] = $brand->id;
            }
        }
    }

    public function updating($property)
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        $query = Product::with('brand')->where('is_active', true);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('short_description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('category', function ($catQuery) {
                      $catQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->selectedCategories)) {
            // Find all child categories of selected categories
            $childCategoryIds = Category::whereIn('parent_id', $this->selectedCategories)->pluck('id')->toArray();
            $allCategoryIds = array_unique(array_merge($this->selectedCategories, $childCategoryIds));
            $query->whereIn('category_id', $allCategoryIds);
        }

        if (!empty($this->selectedBrands)) {
            $query->whereIn('brand_id', $this->selectedBrands);
        }

        $query->whereBetween('price', [$this->minPrice, $this->maxPrice]);

        if ($this->sortBy === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($this->sortBy === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($this->sortBy === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('is_featured', 'desc')->orderBy('name', 'asc');
        }

        return $query->paginate(12);
    }

    public function addToCart(int $productId)
    {
        CartService::add($productId, 1);
        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart-drawer');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedCategories = [];
        $this->selectedBrands = [];
        $this->minPrice = 0;
        $this->maxPrice = 250000;
        $this->sortBy = 'default';
        $this->resetPage();
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Page Header & Search -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Catalog Shop</h1>
            <p class="text-sm text-slate-500 mt-1">Discover, filter, and buy premium hardware products.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full md:w-auto">
            <div class="relative w-full sm:w-72">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search products..." 
                    class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition shadow-sm"
                />
                <span class="absolute left-3.5 top-3 text-slate-450">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>
            
            <select 
                wire:model.live="sortBy"
                class="w-full sm:w-auto bg-white border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-700 focus:outline-none focus:border-indigo-600 transition shadow-sm"
            >
                <option value="default">Sort: Default</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="newest">Sort: Newest</option>
            </select>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Filters -->
        <aside class="space-y-4 lg:col-span-1" x-data="{ mobileOpen: false }">
            <!-- Mobile Filters Toggle -->
            <button 
                type="button"
                @click="mobileOpen = !mobileOpen" 
                class="lg:hidden w-full flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    Filters & Categories
                </span>
                <svg class="h-5 w-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': mobileOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Filters Card (collapsible on mobile, static on desktop) -->
            <div 
                :class="mobileOpen ? 'block' : 'hidden lg:block'" 
                class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 sticky top-24 shadow-sm"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-bold text-slate-850">Filters</h3>
                    <button wire:click="resetFilters" class="text-xs text-indigo-650 hover:text-indigo-500 font-semibold transition">
                        Reset All
                    </button>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Categories</h4>
                    <div class="space-y-3">
                        @foreach($categoriesList as $cat)
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 text-sm text-slate-800 font-bold hover:text-indigo-600 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $cat->id }}" 
                                        wire:model.live="selectedCategories"
                                        class="rounded border-slate-300 bg-slate-50 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-white"
                                      />
                                    <span>{{ $cat->name }}</span>
                                </label>
                                @if($cat->children->isNotEmpty())
                                    <div class="pl-5 space-y-2 border-l border-slate-100 ml-2">
                                        @foreach($cat->children as $child)
                                            <label class="flex items-center gap-2.5 text-xs text-slate-600 hover:text-indigo-600 cursor-pointer select-none">
                                                <input 
                                                    type="checkbox" 
                                                    value="{{ $child->id }}" 
                                                    wire:model.live="selectedCategories"
                                                    class="rounded border-slate-300 bg-slate-50 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-white h-3.5 w-3.5"
                                                  />
                                                <span class="text-slate-400 font-mono">└</span>
                                                <span>{{ $child->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Brands -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Brands</h4>
                    <div class="space-y-2.5">
                        @foreach($brandsList as $brand)
                            <label class="flex items-center gap-3 text-sm text-slate-650 hover:text-slate-900 cursor-pointer select-none">
                                <input 
                                    type="checkbox" 
                                    value="{{ $brand->id }}" 
                                    wire:model.live="selectedBrands"
                                    class="rounded border-slate-300 bg-slate-50 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-white"
                                  />
                                <span>{{ $brand->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Price Range -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Price Range (INR)</h4>
                    <div class="flex items-center gap-3">
                        <input 
                            type="number" 
                            wire:model.live.debounce.500ms="minPrice" 
                            placeholder="Min" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-1.5 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-600"
                        />
                        <span class="text-slate-400">-</span>
                        <input 
                            type="number" 
                            wire:model.live.debounce.500ms="maxPrice" 
                            placeholder="Max" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-1.5 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-600"
                        />
                    </div>
                </div>
            </div>
        </aside>

        <!-- Products List Grid -->
        <main class="lg:col-span-3 space-y-8">
            @if($this->products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6">
                    @foreach($this->products as $prod)
                        <div class="group bg-white border border-slate-200 rounded-xl sm:rounded-2xl overflow-hidden hover:border-indigo-500 hover:shadow-md transition duration-300 flex flex-col h-full">
                            <!-- Image -->
                            <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}" class="aspect-square relative overflow-hidden bg-slate-50 border-b border-slate-100 block">
                                <img src="{{ $prod->images[0] }}" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                                @if($prod->sale_price)
                                    <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-rose-500 text-white text-[9px] sm:text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shadow-sm">
                                        {{ round(100 - ($prod->sale_price / $prod->price * 100)) }}% OFF
                                    </span>
                                @endif
                            </a>
                            <!-- Details -->
                            <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ $prod->brand->name }}</span>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 mt-0.5 sm:mt-1 line-clamp-1 hover:text-indigo-600 transition">
                                        <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}">{{ $prod->name }}</a>
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
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-8 pt-6 border-t border-slate-200">
                    {{ $this->products->links() }}
                </div>
            @else
                <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
                    <svg class="h-12 w-12 text-slate-405 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-bold text-slate-800">No products found</h3>
                    <p class="text-sm text-slate-500 mt-1">Try resetting your filters or search terms.</p>
                    <button wire:click="resetFilters" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-100 border border-slate-200 py-2 px-4 text-xs font-bold text-slate-700 hover:bg-slate-200 hover:text-slate-900 transition">
                        Clear All Filters
                    </button>
                </div>
            @endif
        </main>
    </div>
</div></div>