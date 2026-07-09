<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\ProductVariant;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $stockFilter = 'all'; // all, low
    public $lowStockThreshold = 5;
    
    // Quick adjust values
    public $adjustValues = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'stockFilter' => ['except' => 'all'],
    ];

    public function updating($property)
    {
        if ($property === 'search' || $property === 'stockFilter' || $property === 'lowStockThreshold') {
            $this->resetPage();
        }
    }

    public function updateStock($type, $id, $newStock)
    {
        $newStock = trim($newStock);
        if ($newStock === '' || !is_numeric($newStock)) {
            $this->dispatch('swal', title: 'Error', text: 'Please enter a valid stock number.', icon: 'error');
            return;
        }

        $newStock = (int) $newStock;
        if ($newStock < 0) {
            $this->dispatch('swal', title: 'Error', text: 'Stock level cannot be negative.', icon: 'error');
            return;
        }

        if ($type === 'product') {
            $product = Product::findOrFail($id);
            $product->stock = $newStock;
            $product->save();
            
            $this->dispatch('swal', title: 'Stock Updated', text: "Updated {$product->name} stock to {$newStock}.", icon: 'success');
        } elseif ($type === 'variant') {
            $variant = ProductVariant::with('product')->findOrFail($id);
            $variant->stock = $newStock;
            $variant->save();

            $this->dispatch('swal', title: 'Stock Updated', text: "Updated variant {$variant->product->name} ({$variant->name}) stock to {$newStock}.", icon: 'success');
        }
        
        // Clear adjustment state
        unset($this->adjustValues[$type . '_' . $id]);
    }

    public function render()
    {
        $productsQuery = Product::with(['variants', 'brand', 'category']);

        // Search filter
        if (!empty($this->search)) {
            $productsQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhereHas('variants', function($vq) {
                      $vq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Low stock filter
        if ($this->stockFilter === 'low') {
            $productsQuery->where(function($q) {
                $q->where(function($sq) {
                    $sq->whereDoesntHave('variants')
                       ->where('stock', '<=', $this->lowStockThreshold);
                })
                ->orWhereHas('variants', function($vq) {
                    $vq->where('stock', '<=', $this->lowStockThreshold);
                });
            });
        }

        $products = $productsQuery->orderBy('name', 'asc')->paginate(10);

        return view('components.admin.⚡stock', [
            'products' => $products
        ]);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Stock Management</h1>
            <p class="text-sm text-slate-550 mt-1">Monitor low stock notifications and adjust current item levels on the fly.</p>
        </div>
    </div>

    <!-- Filter Controls Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <!-- Search -->
            <div class="space-y-1.5 col-span-1 sm:col-span-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Search Products / Variants</label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search by name, variant, or SKU..." 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 transition"
                    />
                    <span class="absolute left-3.5 top-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Stock Filter Dropdown -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Inventory Filter</label>
                <select 
                    wire:model.live="stockFilter" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 transition"
                >
                    <option value="all">All Products</option>
                    <option value="low">Low Stock Only</option>
                </select>
            </div>

            <!-- Low Stock Threshold input -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Low Stock Threshold</label>
                <input 
                    type="number" 
                    wire:model.live.debounce.300ms="lowStockThreshold" 
                    min="1"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 transition"
                />
            </div>
        </div>
    </div>

    <!-- Inventory Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-black text-slate-400 uppercase tracking-wider">
                        <th class="py-4 px-6">Product / Variant Details</th>
                        <th class="py-4 px-6">SKU</th>
                        <th class="py-4 px-6">Category / Brand</th>
                        <th class="py-4 px-6">Price</th>
                        <th class="py-4 px-6 text-center">Status</th>
                        <th class="py-4 px-6">Current Stock</th>
                        <th class="py-4 px-6 text-right">Quick Stock Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($products as $prod)
                        @if($prod->variants->isEmpty())
                            <!-- Simple Product Row -->
                            @php
                                $isLow = $prod->stock <= $lowStockThreshold;
                                $isOut = $prod->stock == 0;
                                $adjustKey = 'product_' . $prod->id;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <!-- Image & Name -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-50 border border-slate-200/80 p-0.5 flex-shrink-0">
                                            @if(is_array($prod->images) && count($prod->images) > 0)
                                                <img src="{{ $prod->images[0] }}" class="h-full w-full object-cover rounded-md">
                                            @else
                                                <div class="h-full w-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs">📦</div>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.products.edit', $prod->id) }}" class="text-xs font-bold text-slate-800 hover:text-indigo-650 transition">
                                                {{ $prod->name }}
                                            </a>
                                            <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Simple Product</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- SKU -->
                                <td class="py-4 px-6 text-xs font-mono text-slate-500">
                                    {{ $prod->sku ?? '—' }}
                                </td>

                                <!-- Category / Brand -->
                                <td class="py-4 px-6">
                                    <span class="block text-xs font-bold text-slate-800">{{ $prod->category->name ?? '—' }}</span>
                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $prod->brand->name ?? '—' }}</span>
                                </td>

                                <!-- Price -->
                                <td class="py-4 px-6 text-xs font-bold text-slate-800">
                                    ₹{{ number_format($prod->sale_price ?? $prod->price) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-6 text-center">
                                    @if($isOut)
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700 ring-1 ring-inset ring-rose-600/10">Out of Stock</span>
                                    @elseif($isLow)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700 ring-1 ring-inset ring-amber-600/10">Low Stock</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">In Stock</span>
                                    @endif
                                </td>

                                <!-- Current Stock -->
                                <td class="py-4 px-6">
                                    <div class="text-center sm:text-left">
                                        <span class="text-sm font-extrabold {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                            {{ $prod->stock }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Adjustment Inputs -->
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center gap-1.5" x-data="{ stockVal: {{ $prod->stock }} }">
                                        <button 
                                            @click="if(stockVal > 0) { stockVal--; $wire.set('adjustValues.{{ $adjustKey }}', stockVal) }"
                                            type="button" 
                                            class="p-1 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-100 transition"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                            </svg>
                                        </button>
                                        <input 
                                            type="number" 
                                            x-model.number="stockVal"
                                            @change="$wire.set('adjustValues.{{ $adjustKey }}', stockVal)"
                                            class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-center text-xs font-bold text-slate-800 focus:outline-none focus:border-indigo-650"
                                        />
                                        <button 
                                            @click="stockVal++; $wire.set('adjustValues.{{ $adjustKey }}', stockVal)"
                                            type="button" 
                                            class="p-1 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-100 transition"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="updateStock('product', {{ $prod->id }}, adjustValues.{{ $adjustKey }} ?? stockVal)"
                                            type="button" 
                                            class="ml-2 inline-flex items-center justify-center p-1.5 bg-indigo-650 text-white rounded-lg hover:bg-indigo-750 transition shadow-sm"
                                            title="Save Stock"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <!-- Product with Variants (Group Row Header followed by Variant Rows) -->
                            <tr class="bg-slate-50/30 border-b border-slate-100 font-bold text-slate-500 text-[10px] uppercase tracking-wider">
                                <td colspan="7" class="py-2 px-6">
                                    {{ $prod->name }} <span class="text-slate-400 font-mono text-[9px] ml-1">({{ $prod->variants->count() }} Variants)</span>
                                </td>
                            </tr>
                            @foreach($prod->variants as $variant)
                                @php
                                    $isLow = $variant->stock <= $lowStockThreshold;
                                    $isOut = $variant->stock == 0;
                                    $adjustKey = 'variant_' . $variant->id;
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <!-- Image & Name -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3 pl-4">
                                            <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-50 border border-slate-200/80 p-0.5 flex-shrink-0">
                                                @if($variant->image_path)
                                                    <img src="{{ $variant->image_path }}" class="h-full w-full object-cover rounded-md">
                                                @elseif(is_array($prod->images) && count($prod->images) > 0)
                                                    <img src="{{ $prod->images[0] }}" class="h-full w-full object-cover rounded-md">
                                                @else
                                                    <div class="h-full w-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs">📦</div>
                                                @endif
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.products.edit', $prod->id) }}" class="text-xs font-bold text-slate-800 hover:text-indigo-650 transition">
                                                    {{ $variant->name }}
                                                </a>
                                                <span class="block text-[9px] font-medium text-slate-400 mt-0.5">Parent: {{ $prod->name }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- SKU -->
                                    <td class="py-4 px-6 text-xs font-mono text-slate-500">
                                        {{ $variant->sku ?? $prod->sku ?? '—' }}
                                    </td>

                                    <!-- Category / Brand -->
                                    <td class="py-4 px-6">
                                        <span class="block text-xs font-bold text-slate-800">{{ $prod->category->name ?? '—' }}</span>
                                        <span class="block text-[10px] text-slate-400 mt-0.5">{{ $prod->brand->name ?? '—' }}</span>
                                    </td>

                                    <!-- Price -->
                                    <td class="py-4 px-6 text-xs font-bold text-slate-800">
                                        ₹{{ number_format($variant->price ?? $prod->sale_price ?? $prod->price) }}
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-4 px-6 text-center">
                                        @if($isOut)
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700 ring-1 ring-inset ring-rose-600/10">Out of Stock</span>
                                        @elseif($isLow)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700 ring-1 ring-inset ring-amber-600/10">Low Stock</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">In Stock</span>
                                        @endif
                                    </td>

                                    <!-- Current Stock -->
                                    <td class="py-4 px-6">
                                        <div class="text-center sm:text-left">
                                            <span class="text-sm font-extrabold {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                                {{ $variant->stock }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Adjustment Inputs -->
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center gap-1.5" x-data="{ stockVal: {{ $variant->stock }} }">
                                            <button 
                                                @click="if(stockVal > 0) { stockVal--; $wire.set('adjustValues.{{ $adjustKey }}', stockVal) }"
                                                type="button" 
                                                class="p-1 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-100 transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                                </svg>
                                            </button>
                                            <input 
                                                type="number" 
                                                x-model.number="stockVal"
                                                @change="$wire.set('adjustValues.{{ $adjustKey }}', stockVal)"
                                                class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-center text-xs font-bold text-slate-800 focus:outline-none focus:border-indigo-650"
                                            />
                                            <button 
                                                @click="stockVal++; $wire.set('adjustValues.{{ $adjustKey }}', stockVal)"
                                                type="button" 
                                                class="p-1 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-100 transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="updateStock('variant', {{ $variant->id }}, adjustValues.{{ $adjustKey }} ?? stockVal)"
                                                type="button" 
                                                class="ml-2 inline-flex items-center justify-center p-1.5 bg-indigo-650 text-white rounded-lg hover:bg-indigo-750 transition shadow-sm"
                                                title="Save Stock"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                <svg class="h-12 w-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                No items found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
