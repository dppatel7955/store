<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    // Form fields
    public $name = '';
    public $slug = '';
    public $sku = '';
    public $price = '';
    public $sale_price = '';
    public $stock = 10;
    public array $imagesList = [];
    public $imageFiles = [];
    public $videoFile = null;
    public string $newImageUrl = '';
    public $short_description = '';
    public $description = '';
    public $category_id = '';
    public $brand_id = '';
    public $is_active = true;
    public $is_featured = false;

    // Variants field
    public string $variant_type = 'other';
    public array $variants = [];
    public array $tempImages = []; // index-based variant image uploads (array of arrays)

    public $categoriesList = [];
    public $brandsList = [];

    public function mount()
    {
        $this->categoriesList = Category::orderBy('name')->get();
        $this->brandsList = Brand::orderBy('name')->get();
        $this->category_id = $this->categoriesList->first()->id ?? '';
        $this->brand_id = $this->brandsList->first()->id ?? '';
    }

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function addImageUrl()
    {
        $this->validate([
            'newImageUrl' => 'required|url'
        ], [
            'newImageUrl.url' => 'Please enter a valid image URL.'
        ]);

        $this->imagesList[] = trim($this->newImageUrl);
        $this->newImageUrl = '';
    }

    public function removeImage($index)
    {
        if (isset($this->imagesList[$index])) {
            unset($this->imagesList[$index]);
            $this->imagesList = array_values($this->imagesList);
        }
    }

    public function addVariant()
    {
        $this->variants[] = [
            'name' => '',
            'value' => '',
            'sku' => '',
            'price' => $this->price ?? '',
            'sale_price' => $this->sale_price ?? '',
            'stock' => 0,
            'images' => [],
        ];
    }

    public function removeVariant($index)
    {
        if (isset($this->variants[$index])) {
            unset($this->variants[$index]);
            $this->variants = array_values($this->variants);
        }
        if (isset($this->tempImages[$index])) {
            unset($this->tempImages[$index]);
        }
    }

    public function clearVariantTempImages($index)
    {
        if (isset($this->tempImages[$index])) {
            unset($this->tempImages[$index]);
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:3|max:255',
            'slug' => 'required|max:255|unique:products,slug',
            'sku' => 'nullable|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'required|integer|min:0',
            'imagesList' => 'required_without:imageFiles|array',
            'imageFiles.*' => 'image|max:2048',
            'videoFile' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/webm|max:20480',
            'short_description' => 'nullable|max:500',
            'description' => 'nullable|max:5000',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'variant_type' => 'required|in:color,size,weight,other',
            // Variants validation
            'variants.*.name' => 'required|min:1|max:255',
            'variants.*.value' => 'nullable|max:50',
            'variants.*.sku' => 'nullable|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0|lt:variants.*.price',
            'variants.*.stock' => 'required|integer|min:0',
            'tempImages.*.*' => 'image|max:2048',
        ], [
            'imagesList.required_without' => 'Please upload an image or add at least one image URL.',
            'variants.*.name.required' => 'Variant option details/name is required.',
        ]);

        if ($this->variant_type === 'color' && count($this->variants) > 0) {
            foreach ($this->variants as $index => $variantData) {
                $color = trim((string) ($variantData['value'] ?? ''));
                if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    $this->addError("variants.$index.value", 'Pick a valid color for each variant.');
                    return;
                }
            }
        }

        // Upload main images
        if ($this->imageFiles) {
            foreach ($this->imageFiles as $file) {
                $path = $file->store('products', 'custom_public');
                $this->imagesList[] = '/uploads/' . $path;
            }
        }

        // Upload video file if present
        $videoPath = null;
        if ($this->videoFile) {
            $path = $this->videoFile->store('products/videos', 'custom_public');
            $videoPath = '/uploads/' . $path;
        }

        // Generate product SKU if empty
        do {
            $baseSku = 'SKU-' . strtoupper(Str::random(8));
        } while (Product::whereSku($baseSku)->exists());

        $finalSku = $this->sku ?: $baseSku;

        // Create product
        $product = Product::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $finalSku,
            'price' => $this->price,
            'sale_price' => $this->sale_price ?: null,
            'stock' => $this->stock,
            'images' => $this->imagesList,
            'video_path' => $videoPath,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id ?: null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'variant_type' => $this->variant_type,
        ]);

        // Save variants
        foreach ($this->variants as $index => $variantData) {
            $variantImages = [];
            if (isset($this->tempImages[$index])) {
                foreach ($this->tempImages[$index] as $file) {
                    $path = $file->store('variants', 'custom_public');
                    $variantImages[] = '/uploads/' . $path;
                }
            }

            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variantData['name'],
                'value' => $variantData['value'] ?: null,
                'sku' => $variantData['sku'] ?: ($finalSku . '-' . strtoupper(Str::random(4))),
                'price' => $variantData['price'] ?: null,
                'sale_price' => $variantData['sale_price'] ?: null,
                'stock' => $variantData['stock'],
                'images' => $variantImages,
                'is_active' => true,
            ]);
        }

        $this->dispatch('swal', title: 'Created!', text: 'Product and variants created successfully.', icon: 'success');
        return redirect()->route('admin.products');
    }
};
?>

<div class="space-y-6 max-w-5xl mx-auto py-6">
    <!-- Breadcrumbs -->
    <nav class="text-xs text-slate-500 flex items-center gap-1.5">
        <a href="{{ route('admin.products') }}" class="hover:text-indigo-600 transition">Products</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-bold">Add New Product</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Add Product</h1>
            <p class="text-xs text-slate-500 mt-1">Fill out core details and customize optional variation options.</p>
        </div>
        <a href="{{ route('admin.products') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
            Back to List
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form Column -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Core Details -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">1. Core Details</h2>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="name" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition" placeholder="e.g. Intel Core i9-14900K Processor">
                    @error('name') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">URL Slug <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="slug" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition">
                        @error('slug') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">SKU (Stock Keeping Unit)</label>
                        <input type="text" wire:model="sku" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition" placeholder="Auto-generated if blank">
                        @error('sku') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category <span class="text-rose-500">*</span></label>
                        <select wire:model="category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition">
                            @foreach($categoriesList as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Brand</label>
                        <select wire:model="brand_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition">
                            <option value="">No Brand</option>
                            @foreach($brandsList as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">2. Descriptions</h2>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Short Description</label>
                    <div wire:ignore x-init="
                        $refs.shortTrix.addEventListener('trix-change', () => {
                            $wire.set('short_description', document.getElementById('short-desc-trix').value);
                        });
                    ">
                        <input id="short-desc-trix" type="hidden" value="{{ $short_description }}">
                        <trix-editor x-ref="shortTrix" input="short-desc-trix" class="trix-content min-h-[100px] border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-indigo-600 transition text-slate-850 text-xs p-3 focus:outline-none" placeholder="Brief summary displayed on listings page..."></trix-editor>
                    </div>
                    @error('short_description') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Long Description Overview</label>
                    <div wire:ignore x-init="
                        $refs.longTrix.addEventListener('trix-change', () => {
                            $wire.set('description', document.getElementById('desc-trix').value);
                        });
                    ">
                        <input id="desc-trix" type="hidden" value="{{ $description }}">
                        <trix-editor x-ref="longTrix" input="desc-trix" class="trix-content min-h-[200px] border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-indigo-600 transition text-slate-850 text-xs p-3 focus:outline-none" placeholder="HTML or full text specification details..."></trix-editor>
                    </div>
                    @error('description') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Product Variants -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-sm font-bold text-slate-900">3. Product Variations / Variants</h2>
                    <button type="button" wire:click="addVariant" class="rounded-xl border border-indigo-600 bg-indigo-50 text-indigo-750 px-3 py-1.5 text-[10px] font-bold hover:bg-indigo-100 transition flex items-center gap-1">
                        + Add Variant
                    </button>
                </div>

                @if(count($variants) === 0)
                    <div class="text-center py-6 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                        <svg class="h-8 w-8 text-slate-350 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <h4 class="text-xs font-bold text-slate-600">No Variants Configured</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Click Add Variant to set up variations like size, color, storage, RAM, etc.</p>
                    </div>
                @else
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                        <label class="block text-[10px] font-semibold text-slate-600 mb-1.5">Variant Type</label>
                        <select wire:model.live="variant_type" class="w-full sm:w-64 bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition">
                            @foreach(\App\Models\Product::variantTypes() as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">
                            @if($variant_type === 'color')
                                Customers will see color swatches. Set a color code and optional label (e.g. Red).
                            @elseif($variant_type === 'size')
                                Customers will see size buttons (e.g. S, M, L, XL).
                            @elseif($variant_type === 'weight')
                                Customers will see weight options (e.g. 250g, 500g, 1kg).
                            @else
                                Customers will see standard option buttons with the variant name.
                            @endif
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach($variants as $index => $variant)
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4 relative">
                                <button type="button" wire:click="removeVariant({{ $index }})" class="absolute top-4 right-4 text-rose-500 hover:text-rose-700 text-xs font-bold transition" title="Remove variant">
                                    Remove
                                </button>
                                
                                <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                                    <span>Variant Option #{{ $index + 1 }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @if($variant_type === 'color')
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Color <span class="text-rose-500">*</span></label>
                                            <div class="flex items-center gap-2">
                                                <input type="color" wire:model="variants.{{ $index }}.value" class="h-9 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-0.5">
                                                <input type="text" wire:model="variants.{{ $index }}.value" class="flex-1 bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="#FF0000">
                                            </div>
                                            @error('variants.'.$index.'.value') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Color Label <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="variants.{{ $index }}.name" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="e.g. Red, Navy Blue">
                                            @error('variants.'.$index.'.name') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($variant_type === 'size')
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Size <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="variants.{{ $index }}.name" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="e.g. S, M, L, XL">
                                            @error('variants.'.$index.'.name') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Variant SKU</label>
                                            <input type="text" wire:model="variants.{{ $index }}.sku" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Auto-generated if blank">
                                            @error('variants.'.$index.'.sku') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($variant_type === 'weight')
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Weight <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="variants.{{ $index }}.name" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="e.g. 250g, 500g, 1kg">
                                            @error('variants.'.$index.'.name') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Variant SKU</label>
                                            <input type="text" wire:model="variants.{{ $index }}.sku" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Auto-generated if blank">
                                            @error('variants.'.$index.'.sku') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Option Values / Name <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="variants.{{ $index }}.name" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="e.g. 16GB RAM / 512GB SSD">
                                            @error('variants.'.$index.'.name') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Variant SKU</label>
                                            <input type="text" wire:model="variants.{{ $index }}.sku" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Auto-generated if blank">
                                            @error('variants.'.$index.'.sku') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>

                                @if($variant_type === 'color')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 mb-1">Variant SKU</label>
                                        <input type="text" wire:model="variants.{{ $index }}.sku" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Auto-generated if blank">
                                        @error('variants.'.$index.'.sku') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                @endif

                                 <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                     <div>
                                         <label class="block text-[10px] font-semibold text-slate-600 mb-1">Price Override (₹)</label>
                                         <input type="number" step="0.01" wire:model="variants.{{ $index }}.price" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Base override">
                                         @error('variants.'.$index.'.price') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                     </div>
                                     <div>
                                         <label class="block text-[10px] font-semibold text-slate-600 mb-1">Sale Price Override (₹)</label>
                                         <input type="number" step="0.01" wire:model="variants.{{ $index }}.sale_price" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition" placeholder="Optional sale price">
                                         @error('variants.'.$index.'.sale_price') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                     </div>
                                     <div>
                                         <label class="block text-[10px] font-semibold text-slate-600 mb-1">Stock Quantity <span class="text-rose-500">*</span></label>
                                         <input type="number" wire:model="variants.{{ $index }}.stock" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition">
                                         @error('variants.'.$index.'.stock') <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                     </div>
                                     <div>
                                         <label class="block text-[10px] font-semibold text-slate-600 mb-1">Variant Images <span class="text-slate-400 font-normal">(Multiple)</span></label>
                                         <input type="file" wire:model="tempImages.{{ $index }}" multiple class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                         @error('tempImages.'.$index) <span class="text-rose-500 text-[9px] font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                     </div>
                                 </div>

                                <!-- Image Preview -->
                                @if(isset($tempImages[$index]) && count($tempImages[$index]) > 0)
                                    <div class="space-y-1 mt-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-bold text-slate-450 uppercase">Selected Images Preview ({{ count($tempImages[$index]) }})</span>
                                            <button type="button" wire:click="clearVariantTempImages({{ $index }})" class="text-[9px] font-bold text-rose-600 hover:text-rose-700">Clear All</button>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($tempImages[$index] as $file)
                                                <div class="h-10 w-10 border border-slate-200 rounded-lg overflow-hidden relative group bg-slate-100 flex items-center justify-center">
                                                    <?php try { ?>
                                                        <img src="{{ $file->temporaryUrl() }}" class="h-full w-full object-cover">
                                                    <?php } catch (\Throwable $e) { ?>
                                                        <span class="text-[8px] font-bold text-slate-405 text-center leading-tight">Uploaded</span>
                                                    <?php } ?>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Options (Pricing, Status, Images) -->
        <div class="space-y-6">
            
            <!-- Base Pricing & Inventory -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">4. Pricing & Inventory</h2>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Regular Price (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" wire:model="price" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition" placeholder="₹0.00">
                    @error('price') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sale Price (₹)</label>
                    <input type="number" step="0.01" wire:model="sale_price" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition" placeholder="Leave blank if not on sale">
                    @error('sale_price') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Total Base Stock <span class="text-rose-500">*</span></label>
                    <input type="number" wire:model="stock" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition">
                    @error('stock') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Upload product images -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">5. Main Gallery Images</h2>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Add Image URL</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="newImageUrl" placeholder="https://" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-indigo-600 transition">
                        <button type="button" wire:click="addImageUrl" class="rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 px-3 py-2 text-xs font-bold hover:bg-indigo-100 transition">Add</button>
                    </div>
                    @error('newImageUrl') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Upload Local Files</label>
                    <input type="file" wire:model="imageFiles" multiple class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('imageFiles.*') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Local Uploads Previews -->
                @if($imageFiles && count($imageFiles) > 0)
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Newly Selected Files Preview ({{ count($imageFiles) }})</span>
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                            @foreach($imageFiles as $file)
                                <div class="aspect-square border border-slate-200 rounded-lg overflow-hidden bg-slate-50 relative">
                                    <img src="{{ $file->temporaryUrl() }}" class="h-full w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Preview Thumbnail Grid -->
                @if(count($imagesList) > 0)
                    <div class="space-y-2 pt-2">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Gallery</span>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($imagesList as $index => $imgUrl)
                                <div class="aspect-square border border-slate-200 rounded-lg overflow-hidden bg-slate-50 relative group">
                                    <img src="{{ $imgUrl }}" class="h-full w-full object-cover">
                                    <button type="button" wire:click="removeImage({{ $index }})" class="absolute top-1 right-1 h-4 w-4 bg-rose-500 hover:bg-rose-600 text-white rounded-full flex items-center justify-center text-[9px] shadow transition">
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Product Video -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">6. Product Video (Optional)</h2>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Upload Local Video File (max 20MB)</label>
                    <input type="file" wire:model="videoFile" accept="video/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('videoFile') <span class="text-rose-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                @if($videoFile)
                    <div class="pt-2">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Video Preview</span>
                        <div class="aspect-video w-full rounded-xl overflow-hidden bg-slate-950 border border-slate-200 shadow-sm relative">
                            <?php try { ?>
                                <video src="{{ $videoFile->temporaryUrl() }}" controls class="h-full w-full object-cover"></video>
                            <?php } catch (\Throwable $e) { ?>
                                <div class="p-4 text-center text-xs text-slate-500">Video uploaded: {{ $videoFile->getClientOriginalName() }}</div>
                            <?php } ?>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Settings / Toggles -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">6. Visibility & Meta</h2>
                
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-semibold text-slate-750">Active Listing</span>
                        <span class="block text-[10px] text-slate-400">Visible to customers in storefront shop</span>
                    </div>
                    <button type="button" wire:click="$toggle('is_active')" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-indigo-600' : 'bg-slate-200' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div>
                        <span class="block text-xs font-semibold text-slate-750">Featured Item</span>
                        <span class="block text-[10px] text-slate-400">Recommended in homepage highlights</span>
                    </div>
                    <button type="button" wire:click="$toggle('is_featured')" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_featured ? 'bg-yellow-600' : 'bg-slate-200' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_featured ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

            <!-- Submit action block -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-3">
                <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-xs font-bold text-white shadow-md hover:from-indigo-650 hover:to-purple-700 transition text-center flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="save">Publish Product</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Publishing...
                    </span>
                </button>
                <a href="{{ route('admin.products') }}" class="w-full block rounded-xl bg-white border border-slate-200 py-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center">
                    Discard Changes
                </a>
            </div>
        </div>
    </form>
</div>
