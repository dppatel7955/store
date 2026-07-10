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

        if (request()->has('search') && ! empty(request('search'))) {
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

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('short_description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('category', function ($catQuery) {
                        $catQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if (! empty($this->selectedCategories)) {
            $childCategoryIds = Category::whereIn('parent_id', $this->selectedCategories)->pluck('id')->toArray();
            $allCategoryIds = array_unique(array_merge($this->selectedCategories, $childCategoryIds));
            $query->whereIn('category_id', $allCategoryIds);
        }

        if (! empty($this->selectedBrands)) {
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

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = count($this->selectedCategories) + count($this->selectedBrands);

        if ($this->minPrice > 0 || $this->maxPrice < 250000) {
            $count++;
        }

        if ($this->search !== '') {
            $count++;
        }

        return $count;
    }

    public function addToCart(int $productId)
    {
        CartService::add($productId, 1);
        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart-drawer');
    }

    public function removeCategory(int $id)
    {
        $this->selectedCategories = array_values(array_filter(
            $this->selectedCategories,
            fn ($categoryId) => (int) $categoryId !== $id
        ));
        $this->resetPage();
    }

    public function removeBrand(int $id)
    {
        $this->selectedBrands = array_values(array_filter(
            $this->selectedBrands,
            fn ($brandId) => (int) $brandId !== $id
        ));
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function resetPrice()
    {
        $this->minPrice = 0;
        $this->maxPrice = 250000;
        $this->resetPage();
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

@php
    $allCategories = collect($categoriesList)->flatMap(function ($cat) {
        return collect([$cat])->merge($cat->children ?? []);
    })->keyBy('id');
    $brandsById = collect($brandsList)->keyBy('id');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12" x-data="{ filterOpen: false }" x-effect="document.body.classList.toggle('overflow-hidden', filterOpen)">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Shop</h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $this->products->total() }} {{ Str::plural('product', $this->products->total()) }}
                    @if($this->activeFilterCount > 0)
                        <span class="text-indigo-600 font-semibold">· {{ $this->activeFilterCount }} filters</span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile filter button -->
                <button
                    type="button"
                    @click="filterOpen = true"
                    class="lg:hidden inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 transition min-h-11"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filters
                    @if($this->activeFilterCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] font-bold text-white">{{ $this->activeFilterCount }}</span>
                    @endif
                </button>

                <select
                    wire:model.live="sortBy"
                    class="flex-1 sm:flex-none min-h-11 bg-white border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-700 focus:outline-none focus:border-indigo-600 transition shadow-sm"
                >
                    <option value="default">Featured</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>

        <!-- Search -->
        <div class="relative w-full max-w-xl">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search products, brands, categories..."
                class="w-full min-h-11 bg-white border border-slate-200 rounded-xl py-2.5 pl-10 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition shadow-sm"
            />
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            @if($search !== '')
                <button type="button" wire:click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-1" aria-label="Clear search">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            @endif
        </div>

        <!-- Active filter chips -->
        @if($this->activeFilterCount > 0)
            <div class="flex flex-wrap items-center gap-2">
                @if($search !== '')
                    <button type="button" wire:click="clearSearch" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        “{{ Str::limit($search, 24) }}”
                        <span class="text-indigo-400">&times;</span>
                    </button>
                @endif

                @foreach($selectedCategories as $catId)
                    @php $catId = (int) $catId; @endphp
                    @if($allCategories->has($catId))
                        <button type="button" wire:click="removeCategory({{ $catId }})" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                            {{ $allCategories[$catId]->name }}
                            <span class="text-slate-400">&times;</span>
                        </button>
                    @endif
                @endforeach

                @foreach($selectedBrands as $brandId)
                    @php $brandId = (int) $brandId; @endphp
                    @if($brandsById->has($brandId))
                        <button type="button" wire:click="removeBrand({{ $brandId }})" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                            {{ $brandsById[$brandId]->name }}
                            <span class="text-slate-400">&times;</span>
                        </button>
                    @endif
                @endforeach

                @if($minPrice > 0 || $maxPrice < 250000)
                    <button type="button" wire:click="resetPrice" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                        ₹{{ number_format($minPrice) }} – ₹{{ number_format($maxPrice) }}
                        <span class="text-slate-400">&times;</span>
                    </button>
                @endif

                <button type="button" wire:click="resetFilters" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 px-1">
                    Clear all
                </button>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        <!-- Desktop sidebar -->
        <aside class="hidden lg:block lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-6 sticky top-24 shadow-sm">
                @include('components.partials.shop-filters', ['compact' => false])
            </div>
        </aside>

        <!-- Mobile filter drawer -->
        <div
            x-show="filterOpen"
            class="fixed inset-0 z-50 lg:hidden"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-label="Filters"
        >
            <div
                x-show="filterOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="filterOpen = false"
                class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
            ></div>

            <div
                x-show="filterOpen"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute inset-x-0 bottom-0 max-h-[88vh] bg-white rounded-t-3xl shadow-2xl flex flex-col"
            >
                <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="mx-auto h-1 w-10 rounded-full bg-slate-200 absolute left-1/2 -translate-x-1/2 top-2"></span>
                        <h2 class="text-base font-bold text-slate-900">Filters</h2>
                        @if($this->activeFilterCount > 0)
                            <span class="rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5">{{ $this->activeFilterCount }}</span>
                        @endif
                    </div>
                    <button type="button" @click="filterOpen = false" class="p-2 rounded-xl text-slate-500 hover:bg-slate-50" aria-label="Close filters">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6 overscroll-contain">
                    @include('components.partials.shop-filters', ['compact' => true])
                </div>

                <div class="border-t border-slate-100 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] grid grid-cols-2 gap-3 bg-white">
                    <button type="button" wire:click="resetFilters" class="min-h-11 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                        Reset
                    </button>
                    <button type="button" @click="filterOpen = false" class="min-h-11 rounded-xl bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-500 transition shadow-sm">
                        Show {{ $this->products->total() }} results
                    </button>
                </div>
            </div>
        </div>

        <!-- Products grid -->
        <div class="lg:col-span-3 space-y-6">
            @if($this->products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-5">
                    @foreach($this->products as $prod)
                        @php
                            $image = is_array($prod->images) && count($prod->images) ? $prod->images[0] : 'https://placehold.co/400x400?text=No+Image';
                        @endphp
                        <div wire:key="product-{{ $prod->id }}" class="group bg-white border border-slate-200 rounded-xl sm:rounded-2xl overflow-hidden hover:border-indigo-500 hover:shadow-md transition duration-300 flex flex-col h-full">
                            <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}" class="aspect-square relative overflow-hidden bg-slate-50 border-b border-slate-100 block">
                                <img src="{{ $image }}" loading="lazy" decoding="async" alt="{{ $prod->name }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                                @if($prod->sale_price)
                                    <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-rose-500 text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shadow-sm">
                                        {{ round(100 - ($prod->sale_price / $prod->price * 100)) }}% OFF
                                    </span>
                                @endif
                            </a>
                            <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ $prod->brand?->name ?? 'Brand' }}</span>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 mt-0.5 line-clamp-2 hover:text-indigo-600 transition leading-snug">
                                        <a href="{{ route('shop.detail', ['slug' => $prod->slug]) }}">{{ $prod->name }}</a>
                                    </h3>
                                </div>
                                <div class="mt-3">
                                    <div class="flex items-baseline flex-wrap gap-1.5 mb-3">
                                        @if($prod->sale_price)
                                            <span class="text-sm sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->sale_price) }}</span>
                                            <span class="text-[11px] text-slate-400 line-through">₹{{ number_format($prod->price) }}</span>
                                        @else
                                            <span class="text-sm sm:text-base font-extrabold text-slate-900">₹{{ number_format($prod->price) }}</span>
                                        @endif
                                    </div>

                                    <button
                                        wire:click="addToCart({{ $prod->id }})"
                                        class="w-full min-h-10 rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 text-xs font-bold text-white shadow transition flex items-center justify-center gap-1.5"
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

                <div class="pt-4 border-t border-slate-200 overflow-x-auto">
                    {{ $this->products->links() }}
                </div>
            @else
                <div class="bg-white border border-slate-200 rounded-2xl p-10 sm:p-12 text-center shadow-sm">
                    <svg class="h-12 w-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-bold text-slate-800">No products found</h3>
                    <p class="text-sm text-slate-500 mt-1">Try resetting your filters or search terms.</p>
                    <button wire:click="resetFilters" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-100 border border-slate-200 min-h-11 py-2.5 px-4 text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                        Clear All Filters
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
