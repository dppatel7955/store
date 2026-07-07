<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Banner;
use App\Models\Product;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithFileUploads;

    public string $activeTab = 'banners'; // 'banners' or 'sliders'

    // Database Banners List
    public array $editingBanners = [];

    // New Banner Input Form
    public $newBannerImage = null;
    public string $newBannerUrl = '';
    public int $newBannerSortOrder = 0;

    // Sliders Config Fields
    public string $newArrivalsTitle = 'New Arrivals';
    public string $newArrivalsSubtitle = 'Explore our latest high-performance releases.';
    public string $newArrivalsMode = 'latest'; // latest or selected
    public int $newArrivalsLimit = 4;
    public array $newArrivalsProducts = [];

    public string $featuredTitle = 'Featured Components';
    public string $featuredSubtitle = 'Curated collection of high-performance hardware.';
    public string $featuredMode = 'featured'; // featured or selected
    public int $featuredLimit = 4;
    public array $featuredProducts = [];

    public function mount()
    {
        $this->loadBanners();
        $this->loadSliderSettings();
    }

    public function loadBanners()
    {
        $this->editingBanners = Banner::orderBy('sort_order', 'asc')
            ->get()
            ->toArray();
    }

    public function loadSliderSettings()
    {
        $settingsPath = storage_path('app/home_settings.json');
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            if ($settings) {
                $this->newArrivalsTitle = $settings['new_arrivals_title'] ?? 'New Arrivals';
                $this->newArrivalsSubtitle = $settings['new_arrivals_subtitle'] ?? 'Explore our latest high-performance releases.';
                $this->newArrivalsMode = $settings['new_arrivals_mode'] ?? 'latest';
                $this->newArrivalsLimit = (int) ($settings['new_arrivals_limit'] ?? 4);
                $this->newArrivalsProducts = $settings['new_arrivals_products'] ?? [];

                $this->featuredTitle = $settings['featured_title'] ?? 'Featured Components';
                $this->featuredSubtitle = $settings['featured_subtitle'] ?? 'Curated collection of high-performance hardware.';
                $this->featuredMode = $settings['featured_mode'] ?? 'featured';
                $this->featuredLimit = (int) ($settings['featured_limit'] ?? 4);
                $this->featuredProducts = $settings['featured_products'] ?? [];
            }
        }
    }

    public function saveSliderSettings()
    {
        $this->validate([
            'newArrivalsTitle' => 'required|string|max:255',
            'newArrivalsSubtitle' => 'required|string|max:500',
            'newArrivalsMode' => 'required|in:latest,selected',
            'newArrivalsLimit' => 'required|integer|min:1|max:20',
            'newArrivalsProducts' => 'nullable|array',
            'featuredTitle' => 'required|string|max:255',
            'featuredSubtitle' => 'required|string|max:500',
            'featuredMode' => 'required|in:featured,selected',
            'featuredLimit' => 'required|integer|min:1|max:20',
            'featuredProducts' => 'nullable|array',
        ]);

        $settings = [
            'new_arrivals_title' => $this->newArrivalsTitle,
            'new_arrivals_subtitle' => $this->newArrivalsSubtitle,
            'new_arrivals_mode' => $this->newArrivalsMode,
            'new_arrivals_limit' => (int) $this->newArrivalsLimit,
            'new_arrivals_products' => $this->newArrivalsProducts,
            'featured_title' => $this->featuredTitle,
            'featured_subtitle' => $this->featuredSubtitle,
            'featured_mode' => $this->featuredMode,
            'featured_limit' => (int) $this->featuredLimit,
            'featured_products' => $this->featuredProducts,
        ];

        file_put_contents(storage_path('app/home_settings.json'), json_encode($settings, JSON_PRETTY_PRINT));

        $this->dispatch('swal', title: 'Saved!', text: 'Slider configurations saved successfully.', icon: 'success');
    }

    public function save()
    {
        $this->validate([
            'editingBanners.*.url' => 'nullable|string|max:1000',
            'editingBanners.*.sort_order' => 'required|integer|min:0',
            'editingBanners.*.is_active' => 'required|boolean',
        ]);

        foreach ($this->editingBanners as $item) {
            $banner = Banner::find($item['id']);
            if ($banner) {
                $banner->update([
                    'url' => $item['url'],
                    'sort_order' => (int) $item['sort_order'],
                    'is_active' => (bool) $item['is_active'],
                ]);
            }
        }

        $this->loadBanners();
        $this->dispatch('swal', title: 'Saved!', text: 'Banner changes saved successfully.', icon: 'success');
    }

    public function addBanner()
    {
        $this->validate([
            'newBannerImage' => 'required|image|max:5120', // Max 5MB
            'newBannerUrl' => 'nullable|string|max:1000',
            'newBannerSortOrder' => 'required|integer|min:0',
        ]);

        $imagePath = '/uploads/' . $this->newBannerImage->store('banners', 'custom_public');

        Banner::create([
            'image_path' => $imagePath,
            'url' => $this->newBannerUrl,
            'sort_order' => $this->newBannerSortOrder,
            'is_active' => true,
        ]);

        $this->newBannerImage = null;
        $this->newBannerUrl = '';
        $this->newBannerSortOrder = 0;

        $this->loadBanners();
        $this->dispatch('swal', title: 'Uploaded!', text: 'New banner added successfully.', icon: 'success');
    }

    public function deleteBanner($id)
    {
        $banner = Banner::find($id);
        if ($banner) {
            $filePath = str_replace('/uploads/', '', $banner->image_path);
            \Illuminate\Support\Facades\Storage::disk('custom_public')->delete($filePath);
            $banner->delete();
        }

        $this->loadBanners();
        $this->dispatch('swal', title: 'Deleted!', text: 'Banner removed successfully.', icon: 'success');
    }

    #[Computed]
    public function allProducts()
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }
};
?>

<div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Home Settings</h1>
            <p class="text-xs text-slate-500 mt-1">Configure layout, slider headings, banner uploads, and featured items.</p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button 
                wire:click="$set('activeTab', 'banners')"
                class="shrink-0 border-b-2 py-3 px-1 text-sm font-bold transition duration-150 {{ $activeTab === 'banners' ? 'border-indigo-600 text-indigo-650' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                Banners Slides
            </button>
            <button 
                wire:click="$set('activeTab', 'sliders')"
                class="shrink-0 border-b-2 py-3 px-1 text-sm font-bold transition duration-150 {{ $activeTab === 'sliders' ? 'border-indigo-600 text-indigo-650' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                Homepage Sliders Setup
            </button>
        </nav>
    </div>

    @if($activeTab === 'banners')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Banner List & Editor -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-lg font-bold text-slate-900">Active Slider Banners</h2>
                        <span class="text-xs text-slate-400 font-semibold">Ordered by Sort Value</span>
                    </div>

                    @if(count($editingBanners) > 0)
                        <form wire:submit="save" class="space-y-6">
                            <div class="divide-y divide-slate-100 space-y-6">
                                @foreach($editingBanners as $index => $banner)
                                    <div class="pt-6 {{ $index === 0 ? 'pt-0' : '' }} flex flex-col md:flex-row gap-6 items-start">
                                        <!-- Banner Image Preview -->
                                        <div class="w-full md:w-44 aspect-[16/9] rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm flex-shrink-0">
                                            <img src="{{ $banner['image_path'] }}" alt="Banner preview" class="h-full w-full object-cover">
                                        </div>

                                        <!-- Editable Parameters -->
                                        <div class="flex-grow w-full grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Redirect Target URL</label>
                                                <input 
                                                    type="text" 
                                                    wire:model="editingBanners.{{ $index }}.url" 
                                                    placeholder="e.g. /shop?category=cpus" 
                                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Sort Order</label>
                                                <input 
                                                    type="number" 
                                                    wire:model="editingBanners.{{ $index }}.sort_order" 
                                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-1.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                                />
                                            </div>

                                            <div class="flex items-center justify-between pt-5">
                                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                                    <input 
                                                        type="checkbox" 
                                                        wire:model="editingBanners.{{ $index }}.is_active" 
                                                        class="rounded text-indigo-600 focus:ring-indigo-600 border-slate-300"
                                                    />
                                                    <span class="text-xs font-bold text-slate-700">Show on Home Page</span>
                                                </label>

                                                <button 
                                                    type="button" 
                                                    wire:click="deleteBanner({{ $banner['id'] }})"
                                                    wire:confirm="Are you sure you want to remove this banner?"
                                                    class="text-xs font-bold text-rose-600 hover:text-rose-700 transition"
                                                >
                                                    Remove Slide
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-slate-200 pt-5 flex items-center justify-end">
                                <button 
                                    type="submit"
                                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:bg-indigo-500 transition"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <svg class="h-10 w-10 text-slate-350 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-xs text-slate-400 font-bold">No active banners uploaded.</p>
                            <p class="text-[10px] text-slate-400 mt-1">Upload your first image on the right panel.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Add New Banner Form -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm sticky top-24">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-200 pb-3">Add New Slide</h2>

                    <form wire:submit="addBanner" class="space-y-5">
                        <!-- Image Upload -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Banner Image (Required)</label>
                            <div class="space-y-3">
                                @if($newBannerImage)
                                    <div class="relative aspect-[16/9] rounded-xl overflow-hidden border border-indigo-200 bg-slate-50 shadow-sm bg-slate-100 flex items-center justify-center">
                                        <?php try { ?>
                                            <img src="{{ $newBannerImage->temporaryUrl() }}" alt="Banner preview" class="h-full w-full object-cover">
                                        <?php } catch (\Throwable $e) { ?>
                                            <span class="text-xs font-bold text-slate-400 text-center leading-tight px-1">Uploaded</span>
                                        <?php } ?>
                                    </div>
                                @else
                                    <label class="flex flex-col items-center justify-center w-full aspect-[16/9] border-2 border-slate-200 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/50 transition">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-5">
                                            <svg class="w-8 h-8 text-slate-400 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <p class="text-[10px] text-slate-500 font-bold">Upload 16:9 Banner</p>
                                        </div>
                                        <input type="file" wire:model="newBannerImage" accept="image/*" class="hidden" />
                                    </label>
                                @endif
                            </div>
                            @error('newBannerImage') <span class="text-[10px] text-rose-600 font-semibold block mt-1.5">{{ $message }}</span> @enderror
                        </div>

                        <!-- Target URL -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Redirect URL (Optional)</label>
                            <input 
                                type="text" 
                                wire:model="newBannerUrl" 
                                placeholder="e.g. /shop?brand=intel" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                            />
                            @error('newBannerUrl') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Sort Order Value</label>
                            <input 
                                type="number" 
                                wire:model="newBannerSortOrder" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                            />
                            @error('newBannerSortOrder') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <button 
                            type="submit"
                            class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500 py-3 text-xs font-bold text-white shadow-sm transition"
                        >
                            Upload Slide
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'sliders')
        <form wire:submit.prevent="saveSliderSettings" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- New Arrivals Slider -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-250 pb-3 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-650"></span>
                        New Arrivals Slider
                    </h2>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Title</label>
                        <input 
                            type="text" 
                            wire:model="newArrivalsTitle" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('newArrivalsTitle') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Subtitle -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Subtitle</label>
                        <textarea 
                            rows="2" 
                            wire:model="newArrivalsSubtitle" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        ></textarea>
                        @error('newArrivalsSubtitle') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Limit -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Display Limit</label>
                        <input 
                            type="number" 
                            wire:model="newArrivalsLimit" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('newArrivalsLimit') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Mode -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Product Selection Mode</label>
                        <select 
                            wire:model.live="newArrivalsMode"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="latest">Latest Products (Auto-query by date)</option>
                            <option value="selected">Choose Products (Select specific products manually)</option>
                        </select>
                    </div>

                    <!-- Products List (Only if mode is selected) -->
                    @if($newArrivalsMode === 'selected')
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500">Pick Products to Show</label>
                            <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-48 overflow-y-auto space-y-2">
                                @foreach($this->allProducts as $p)
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input 
                                            type="checkbox" 
                                            value="{{ $p->id }}" 
                                            wire:model="newArrivalsProducts" 
                                            class="rounded text-indigo-600 focus:ring-indigo-600 border-slate-300"
                                        />
                                        <span class="text-xs text-slate-750 font-medium">{{ $p->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Featured Slider -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-250 pb-3 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-650"></span>
                        Featured Products Slider
                    </h2>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Title</label>
                        <input 
                            type="text" 
                            wire:model="featuredTitle" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('featuredTitle') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Subtitle -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Subtitle</label>
                        <textarea 
                            rows="2" 
                            wire:model="featuredSubtitle" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        ></textarea>
                        @error('featuredSubtitle') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Limit -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Display Limit</label>
                        <input 
                            type="number" 
                            wire:model="featuredLimit" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('featuredLimit') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Mode -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Product Selection Mode</label>
                        <select 
                            wire:model.live="featuredMode"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="featured">Featured Tag (Auto-query by Featured status)</option>
                            <option value="selected">Choose Products (Select specific products manually)</option>
                        </select>
                    </div>

                    <!-- Products List (Only if mode is selected) -->
                    @if($featuredMode === 'selected')
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500">Pick Products to Show</label>
                            <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-48 overflow-y-auto space-y-2">
                                @foreach($this->allProducts as $p)
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input 
                                            type="checkbox" 
                                            value="{{ $p->id }}" 
                                            wire:model="featuredProducts" 
                                            class="rounded text-indigo-600 focus:ring-indigo-600 border-slate-300"
                                        />
                                        <span class="text-xs text-slate-750 font-medium">{{ $p->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer Save -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                <span class="text-xs text-slate-500">Ensure to save changes to apply immediately to homepage.</span>
                <button 
                    type="submit"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:bg-indigo-500 transition"
                >
                    Save Slider Configs
                </button>
            </div>
        </form>
    @endif
</div>
