<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $filterBrand = '';
    public $isOpen = false;

    // Form fields
    public $productId = null;
    public $name = '';
    public $slug = '';
    public $sku = '';
    public $price = '';
    public $sale_price = '';
    public $stock = 0;
    public $imageUrls = '';
    public $short_description = '';
    public $description = '';
    public $category_id = '';
    public $brand_id = '';
    public $is_active = true;
    public $is_featured = false;

    public $categoriesList = [];
    public $brandsList = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterBrand' => ['except' => '']
    ];

    public function mount()
    {
        $this->categoriesList = Category::orderBy('name')->get();
        $this->brandsList = Brand::orderBy('name')->get();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterBrand() { $this->resetPage(); }

    public function updatedName($value)
    {
        if (!$this->productId) {
            $this->slug = Str::slug($value);
        }
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['category', 'brand'])
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterCategory, function ($query) {
                $query->where('category_id', $this->filterCategory);
            })
            ->when($this->filterBrand, function ($query) {
                $query->where('brand_id', $this->filterBrand);
            })
            ->latest('id')
            ->paginate(10);
    }

    public function openModal($id = null)
    {
        $this->resetErrorBag();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->slug = $product->slug;
            $this->sku = $product->sku ?? '';
            $this->price = $product->price;
            $this->sale_price = $product->sale_price;
            $this->stock = $product->stock;
            $this->imageUrls = is_array($product->images) ? implode(", ", $product->images) : '';
            $this->short_description = $product->short_description;
            $this->description = $product->description;
            $this->category_id = $product->category_id;
            $this->brand_id = $product->brand_id;
            $this->is_active = (bool) $product->is_active;
            $this->is_featured = (bool) $product->is_featured;
        } else {
            $this->resetFields();
        }

        $this->isOpen = true;
    }

    public function resetFields()
    {
        $this->productId = null;
        $this->name = '';
        $this->slug = '';
        $this->sku = '';
        $this->price = '';
        $this->sale_price = '';
        $this->stock = 10;
        $this->imageUrls = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop';
        $this->short_description = '';
        $this->description = '';
        $this->category_id = $this->categoriesList->first()->id ?? '';
        $this->brand_id = $this->brandsList->first()->id ?? '';
        $this->is_active = true;
        $this->is_featured = false;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:3|max:255',
            'slug' => 'required|max:255|unique:products,slug,' . ($this->productId ?? 'NULL') . ',id',
            'sku' => 'nullable|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'required|integer|min:0',
            'imageUrls' => 'required',
            'short_description' => 'nullable|max:500',
            'description' => 'nullable|max:5000',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'required|boolean',
            'is_featured' => 'required|boolean'
        ]);

        $images = array_filter(array_map('trim', explode(',', $this->imageUrls)));

        Product::updateOrCreate(
            ['id' => $this->productId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'sku' => $this->sku ?: ('SKU-' . strtoupper(Str::random(6))),
                'price' => $this->price,
                'sale_price' => $this->sale_price ?: null,
                'stock' => $this->stock,
                'images' => $images,
                'short_description' => $this->short_description,
                'description' => $this->description,
                'category_id' => $this->category_id,
                'brand_id' => $this->brand_id ?: null,
                'is_active' => $this->is_active,
                'is_featured' => $this->is_featured,
            ]
        );

        session()->flash('success', $this->productId ? 'Product updated successfully.' : 'Product created successfully.');
        $this->isOpen = false;
        $this->resetFields();
    }

    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();
        session()->flash('success', 'Status updated successfully.');
    }

    public function toggleFeatured($id)
    {
        $product = Product::findOrFail($id);
        $product->is_featured = !$product->is_featured;
        $product->save();
        session()->flash('success', 'Featured status updated successfully.');
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        session()->flash('success', 'Product deleted successfully.');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Products</h1>
            <p class="text-xs text-slate-500 mt-1">Manage, add, and restock inventory products.</p>
        </div>
        <button 
            wire:click="openModal()" 
            class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
        >
            Add Product
        </button>
    </div>

    <!-- Status Messages -->
    @if (session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Toolbars / Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Search -->
        <div class="flex-1 max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search name, SKU..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-805 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>

        <!-- Filters Dropdown -->
        <div class="flex flex-wrap items-center gap-3">
            <select 
                wire:model.live="filterCategory" 
                class="bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-500"
            >
                <option value="">All Categories</option>
                @foreach($categoriesList as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select 
                wire:model.live="filterBrand" 
                class="bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-500"
            >
                <option value="">All Brands</option>
                @foreach($brandsList as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Product</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">SKU</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Brand</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Price</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Stock</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Active</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Featured</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->products as $product)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- Product Image & Name -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ is_array($product->images) ? ($product->images[0] ?? '') : '' }}" alt="{{ $product->name }}" class="h-10 w-10 object-cover rounded-lg border border-slate-200 flex-shrink-0">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800 leading-normal">{{ $product->name }}</span>
                                        <span class="text-[10px] text-slate-450 font-mono">ID: {{ $product->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <!-- SKU -->
                            <td class="p-4 text-xs text-slate-500 font-mono">{{ $product->sku ?? '-' }}</td>
                            <!-- Category -->
                            <td class="p-4 text-xs text-slate-700">{{ $product->category->name ?? '-' }}</td>
                            <!-- Brand -->
                            <td class="p-4 text-xs text-slate-550">{{ $product->brand->name ?? '-' }}</td>
                            <!-- Price -->
                            <td class="p-4">
                                <div class="flex flex-col">
                                    @if($product->sale_price)
                                        <span class="text-xs font-extrabold text-slate-900">₹{{ number_format($product->sale_price) }}</span>
                                        <span class="text-[10px] text-slate-400 line-through">₹{{ number_format($product->price) }}</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-900">₹{{ number_format($product->price) }}</span>
                                    @endif
                                </div>
                            </td>
                            <!-- Stock -->
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold @if($product->stock < 5) bg-rose-50 border border-rose-200 text-rose-700 @else bg-slate-100 border border-slate-200 text-slate-700 @endif">
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <!-- Active Status -->
                            <td class="p-4">
                                <button 
                                    wire:click="toggleActive({{ $product->id }})"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $product->is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $product->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <!-- Featured Status -->
                            <td class="p-4">
                                <button 
                                    wire:click="toggleFeatured({{ $product->id }})"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $product->is_featured ? 'bg-yellow-600' : 'bg-slate-200' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $product->is_featured ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <!-- Actions -->
                            <td class="p-4 text-right space-x-2">
                                <button 
                                    wire:click="openModal({{ $product->id }})" 
                                    class="text-indigo-605 hover:text-indigo-700 text-xs font-bold transition"
                                >
                                    Edit
                                </button>
                                <button 
                                    wire:confirm="Are you sure you want to delete this product?"
                                    wire:click="delete({{ $product->id }})" 
                                    class="text-rose-605 hover:text-rose-700 text-xs font-bold transition"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-xs text-slate-400 font-semibold">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->products->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->products->links() }}
            </div>
        @endif
    </div>

    <!-- Edit/Create Modal (Scrollable) -->
    <div 
        x-data="{ show: @entangle('isOpen') }" 
        x-show="show" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" 
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="show = false" 
            class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"
        ></div>

        <!-- Modal panel -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white border border-slate-200 shadow-2xl transition-all"
        >
            <div class="px-6 py-6 bg-white border-b border-slate-200 flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-base font-extrabold text-slate-900">
                    {{ $productId ? 'Edit Product' : 'Create Product' }}
                </h3>
                <button @click="show = false" class="text-slate-500 hover:text-slate-805 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Product Name</label>
                        <input type="text" wire:model.live="name" placeholder="e.g. iPhone 15 Pro" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"/>
                        @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-505 mb-1.5">Slug</label>
                        <input type="text" wire:model="slug" placeholder="iphone-15-pro" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition font-mono"/>
                        @error('slug') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">SKU (Optional)</label>
                        <input type="text" wire:model="sku" placeholder="IPHONE-15-PRO-256" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition font-mono"/>
                        @error('sku') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Stock -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Stock Level</label>
                        <input type="number" wire:model="stock" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"/>
                        @error('stock') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Price -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Base Price (₹)</label>
                        <input type="text" wire:model="price" placeholder="79999" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"/>
                        @error('price') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Sale Price -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Sale Price (₹, Optional)</label>
                        <input type="text" wire:model="sale_price" placeholder="74999" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"/>
                        @error('sale_price') <span class="text-[10px] text-rose-605 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Category</label>
                        <select wire:model="category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition">
                            <option value="">Select Category</option>
                            @foreach($categoriesList as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Brand -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-550 mb-1.5">Brand</label>
                        <select wire:model="brand_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition">
                            <option value="">Select Brand</option>
                            @foreach($brandsList as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Image URLs -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Image URLs (comma-separated)</label>
                    <textarea wire:model.live.debounce.500ms="imageUrls" rows="2" placeholder="https://image1.jpg, https://image2.jpg" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"></textarea>
                    @error('imageUrls') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    
                    @php
                        $previewImages = array_filter(array_map('trim', explode(',', $imageUrls)));
                    @endphp
                    @if(!empty($previewImages))
                        <div class="mt-3">
                            <span class="text-[10px] text-slate-450 block mb-1.5">Images Preview ({{ count($previewImages) }} detected)</span>
                            <div class="flex gap-2 overflow-x-auto pb-2">
                                @foreach($previewImages as $img)
                                    @if(filter_var($img, FILTER_VALIDATE_URL))
                                        <img src="{{ $img }}" class="h-14 w-14 object-cover rounded-lg border border-slate-200 bg-slate-55 flex-shrink-0" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop'">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Short Description -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Short Description</label>
                    <input type="text" wire:model="short_description" placeholder="Brief overview displayed on product cards..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"/>
                    @error('short_description') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Detailed Description</label>
                    <textarea wire:model="description" rows="5" placeholder="Full product specifications and details..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"></textarea>
                    @error('description') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Switches -->
                <div class="grid grid-cols-2 gap-4 border-t border-slate-200 pt-4">
                    <!-- Active Switch -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-700">Active Listing</span>
                        <button type="button" wire:click="$toggle('is_active')" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-indigo-600' : 'bg-slate-200' }}">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    <!-- Featured Switch -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-700">Featured Item</span>
                        <button type="button" wire:click="$toggle('is_featured')" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_featured ? 'bg-yellow-600' : 'bg-slate-200' }}">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_featured ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                </div>

                <!-- Footer buttons -->
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 mt-6">
                    <button type="button" @click="show = false" class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-707 hover:bg-slate-202 transition">Cancel</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
