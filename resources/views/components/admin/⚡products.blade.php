<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterCategory = '';
    public $filterBrand = '';
    public $csvFile;

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
        $product = Product::with('variants')->findOrFail($id);
        
        // Delete main images
        if (is_array($product->images)) {
            foreach ($product->images as $img) {
                if ($img && !str_starts_with($img, 'http')) {
                    $path = public_path(ltrim($img, '/'));
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }
        }

        // Delete variant images
        foreach ($product->variants as $variant) {
            if (is_array($variant->images)) {
                foreach ($variant->images as $img) {
                    if ($img && !str_starts_with($img, 'http')) {
                        $path = public_path(ltrim($img, '/'));
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }
            }
        }

        $product->delete();
        $this->dispatch('swal', title: 'Deleted!', text: 'Product deleted successfully.', icon: 'success');
    }

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt,xlsx,xls|max:4096',
        ]);

        try {
            $import = new \App\Imports\ProductsImport;
            \Excel::import($import, $this->csvFile->getRealPath());

            $this->dispatch('swal', 
                title: 'Import Completed!', 
                text: "Successfully imported {$import->getImportedProductsCount()} products and {$import->getImportedVariantsCount()} variations.", 
                icon: 'success'
            );
        } catch (\Exception $e) {
            $this->dispatch('swal', title: 'Import Failed!', text: $e->getMessage(), icon: 'error');
        }
        $this->resetPage();
    }

    public function exportCsv()
    {
        $fileName = 'products_export_' . now()->format('Y_m_d_His') . '.csv';
        return \Excel::download(new \App\Exports\ProductsExport, $fileName);
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
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <a 
                href="/sample_products_import.csv" 
                download="sample_products_import.csv"
                class="rounded-xl bg-white border border-slate-205 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center flex items-center justify-center gap-1.5 shadow-sm"
                title="Download Sample CSV Template"
            >
                <span>Sample CSV</span>
                <svg class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            </a>
            <button 
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                class="rounded-xl bg-white border border-slate-205 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center flex items-center justify-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                title="Export Products Catalog to CSV"
            >
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-3.5 w-3.5 text-slate-550" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Exporting...</span>
                </span>
                <svg wire:loading.remove wire:target="exportCsv" class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </button>
            <label 
                wire:loading.class="opacity-60 cursor-not-allowed pointer-events-none" 
                wire:target="csvFile"
                class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition text-center cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
            >
                <span wire:loading.remove wire:target="csvFile">Import CSV</span>
                <span wire:loading wire:target="csvFile" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-3.5 w-3.5 text-slate-550" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Importing...</span>
                </span>
                <input type="file" wire:model="csvFile" class="hidden" accept=".csv" wire:loading.attr="disabled" wire:target="csvFile">
                <svg wire:loading.remove wire:target="csvFile" class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V4a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
            </label>
            <a 
                href="{{ route('admin.products.create') }}" 
                class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition text-center"
            >
                Add Product
            </a>
        </div>
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
