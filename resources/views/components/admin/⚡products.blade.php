<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $filterBrand = '';

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

    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();
        $this->dispatch('swal', title: 'Success!', text: 'Product active status updated successfully.', icon: 'success');
    }

    public function toggleFeatured($id)
    {
        $product = Product::findOrFail($id);
        $product->is_featured = !$product->is_featured;
        $product->save();
        $this->dispatch('swal', title: 'Success!', text: 'Product featured status updated successfully.', icon: 'success');
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        $this->dispatch('swal', title: 'Deleted!', text: 'Product deleted successfully.', icon: 'success');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Products</h1>
            <p class="text-xs text-slate-500 mt-1">Manage, add, and restock inventory products.</p>
        </div>
        <a 
            href="{{ route('admin.products.create') }}" 
            class="w-full sm:w-auto rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition text-center"
        >
            Add Product
        </a>
    </div>

    <!-- Toolbars / Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Search -->
        <div class="relative flex-grow max-w-md">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search products by name, SKU, desc..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-700 focus:outline-none focus:border-indigo-650 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>

        <!-- Filter Selects -->
        <div class="flex items-center gap-3">
            <select 
                wire:model.live="filterCategory" 
                class="bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-650 focus:outline-none focus:border-indigo-650"
            >
                <option value="">All Categories</option>
                @foreach($categoriesList as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select 
                wire:model.live="filterBrand" 
                class="bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-650 focus:outline-none focus:border-indigo-650"
            >
                <option value="">All Brands</option>
                @foreach($brandsList as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Products Grid / Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        @if($this->products->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider">
                            <th class="p-4">Thumbnail</th>
                            <th class="p-4">Product Details</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Brand</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Stock</th>
                            <th class="p-4">Active</th>
                            <th class="p-4">Featured</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($this->products as $product)
                            <tr class="hover:bg-slate-50/50 transition">
                                <!-- Thumbnail -->
                                <td class="p-4">
                                    <div class="h-12 w-12 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden">
                                        <img src="{{ $product->images[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop' }}" alt="Product Image" class="h-full w-full object-cover">
                                    </div>
                                </td>
                                <!-- Details -->
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $product->name }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">SKU: {{ $product->sku ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">Slug: {{ $product->slug }}</div>
                                </td>
                                <!-- Category -->
                                <td class="p-4 text-slate-600">
                                    {{ $product->category->name ?? 'Uncategorized' }}
                                </td>
                                <!-- Brand -->
                                <td class="p-4 text-slate-600">
                                    {{ $product->brand->name ?? 'N/A' }}
                                </td>
                                <!-- Price -->
                                <td class="p-4">
                                    @if($product->sale_price)
                                        <div class="font-bold text-slate-800">₹{{ number_format($product->sale_price) }}</div>
                                        <div class="text-[10px] text-slate-400 line-through">₹{{ number_format($product->price) }}</div>
                                    @else
                                        <div class="font-bold text-slate-800">₹{{ number_format($product->price) }}</div>
                                    @endif
                                </td>
                                <!-- Stock -->
                                <td class="p-4 font-semibold text-slate-700">
                                    {{ $product->stock }}
                                </td>
                                <!-- Active status -->
                                <td class="p-4">
                                    <button 
                                        wire:click="toggleActive({{ $product->id }})" 
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $product->is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $product->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </td>
                                <!-- Featured status -->
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
                                    <a 
                                        href="{{ route('admin.products.edit', ['id' => $product->id]) }}" 
                                        class="text-indigo-605 hover:text-indigo-700 text-xs font-bold transition"
                                    >
                                        Edit
                                    </a>
                                    <button 
                                        wire:confirm="Are you sure you want to delete this product?"
                                        wire:click="delete({{ $product->id }})" 
                                        class="text-rose-605 hover:text-rose-700 text-xs font-bold transition"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Pagination footer -->
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->products->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="h-12 w-12 text-slate-350 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-sm font-bold text-slate-700">No Products Found</h3>
                <p class="text-xs text-slate-500 mt-1">Try refining your search terms or filters.</p>
            </div>
        @endif
    </div>
</div>
