<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Banner;
use App\Models\Product;
use App\Services\HomeSettingsService;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Cache;
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

    // Dynamic Sliders Array
    public array $sliders = [];

    // Google Analytics ID
    public string $googleAnalyticsId = '';

    // Global Free Shipping Threshold
    public $globalFreeShippingThreshold = 999;

    // Top Header Announcement & Deals
    public bool $announcementEnabled = true;
    public string $announcementText = '⚡ Special Offer: Use Code SAVE10 for Extra 10% OFF + FREE Express Shipping!';
    public string $announcementCoupon = 'SAVE10';
    public string $announcementCountdown = '';
    public string $whatsappNumber = '919876543210';

    public function mount()
    {
        $this->announcementCountdown = now()->addDays(2)->format('Y-m-d\TH:i');
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
                if (isset($settings['sliders'])) {
                    $this->sliders = $settings['sliders'];
                }
                if (isset($settings['google_analytics_id'])) {
                    $this->googleAnalyticsId = $settings['google_analytics_id'];
                }
                if (isset($settings['global_free_shipping_threshold'])) {
                    $this->globalFreeShippingThreshold = $settings['global_free_shipping_threshold'];
                }
                if (isset($settings['announcement_enabled'])) {
                    $this->announcementEnabled = (bool) $settings['announcement_enabled'];
                }
                if (isset($settings['announcement_text'])) {
                    $this->announcementText = $settings['announcement_text'];
                }
                if (isset($settings['announcement_coupon'])) {
                    $this->announcementCoupon = $settings['announcement_coupon'];
                }
                if (isset($settings['announcement_countdown'])) {
                    $this->announcementCountdown = $settings['announcement_countdown'];
                }
                if (isset($settings['whatsapp_number'])) {
                    $this->whatsappNumber = $settings['whatsapp_number'];
                }
            }
        }

        if (empty($this->sliders)) {
            $this->sliders = [
                [
                    'id' => 'new_arrivals',
                    'title' => 'New Arrivals',
                    'subtitle' => 'Explore our latest high-performance releases.',
                    'mode' => 'latest',
                    'limit' => 4,
                    'product_ids' => []
                ],
                [
                    'id' => 'featured',
                    'title' => 'Featured Products',
                    'subtitle' => 'Curated collection of our best premium products.',
                    'mode' => 'featured',
                    'limit' => 4,
                    'product_ids' => []
                ]
            ];
        }
    }

    public function addCustomSlider()
    {
        $this->sliders[] = [
            'id' => 'slider_' . time(),
            'title' => 'New Promo Slider',
            'subtitle' => 'Curated selection of our best products.',
            'mode' => 'latest',
            'limit' => 4,
            'product_ids' => []
        ];
        $this->dispatch('swal', title: 'Added!', text: 'New slider section added. Set titles and save.', icon: 'success');
    }

    public function deleteSlider($index)
    {
        unset($this->sliders[$index]);
        $this->sliders = array_values($this->sliders);
        $this->dispatch('swal', title: 'Removed!', text: 'Slider section removed.', icon: 'info');
    }

    public function saveSliderSettings()
    {
        $this->validate([
            'sliders.*.title' => 'required|string|max:255',
            'sliders.*.subtitle' => 'required|string|max:500',
            'sliders.*.mode' => 'required|in:latest,featured,selected',
            'sliders.*.limit' => 'required|integer|min:1|max:20',
            'sliders.*.product_ids' => 'nullable|array',
            'googleAnalyticsId' => 'nullable|string|max:100',
            'globalFreeShippingThreshold' => 'required|numeric|min:0',
        ]);

        $settingsPath = storage_path('app/home_settings.json');
        $settings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : [];
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings['sliders'] = $this->sliders;
        $settings['google_analytics_id'] = trim($this->googleAnalyticsId);
        $settings['global_free_shipping_threshold'] = (float) $this->globalFreeShippingThreshold;

        file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
        $this->clearHomeCache();

        $this->dispatch('swal', title: 'Saved!', text: 'Home settings updated successfully.', icon: 'success');
    }

    public function saveOffersSettings()
    {
        $this->validate([
            'announcementText' => 'required|string|max:500',
            'announcementCoupon' => 'nullable|string|max:50',
            'announcementCountdown' => 'nullable|string',
            'whatsappNumber' => 'required|string|max:20',
            'globalFreeShippingThreshold' => 'required|numeric|min:0',
        ]);

        $settingsPath = storage_path('app/home_settings.json');
        $settings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : [];
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings['announcement_enabled'] = (bool) $this->announcementEnabled;
        $settings['announcement_text'] = trim($this->announcementText);
        $settings['announcement_coupon'] = strtoupper(trim($this->announcementCoupon));
        $settings['announcement_countdown'] = $this->announcementCountdown;
        $settings['whatsapp_number'] = preg_replace('/[^0-9]/', '', (string) $this->whatsappNumber);
        $settings['global_free_shipping_threshold'] = (float) $this->globalFreeShippingThreshold;

        file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
        $this->clearHomeCache();

        $this->dispatch('swal', title: 'Saved!', text: 'Special offers and announcement settings updated successfully.', icon: 'success');
    }

    public function clearHomeCache(): void
    {
        HomeSettingsService::clearCache();
        Cache::forget('home_categories');
        Cache::forget('home_brands');
        Cache::forget('home_banners');
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
        $this->clearHomeCache();
        $this->dispatch('swal', title: 'Saved!', text: 'Banner changes saved successfully.', icon: 'success');
    }

    public function addBanner()
    {
        $this->validate([
            'newBannerImage' => 'required|image|max:5120', // Max 5MB
            'newBannerUrl' => 'nullable|string|max:1000',
            'newBannerSortOrder' => 'required|integer|min:0',
        ]);

        $imagePath = ImageUploadService::store($this->newBannerImage, 'banners', maxWidth: 1920, quality: 80);

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
        $this->clearHomeCache();
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
        $this->clearHomeCache();
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
            <p class="text-xs text-slate-500 mt-1">Configure layout, dynamic slider sections, banners, and featured items.</p>
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
            <button 
                wire:click="$set('activeTab', 'offers')"
                class="shrink-0 border-b-2 py-3 px-1 text-sm font-bold transition duration-150 flex items-center gap-1.5 {{ $activeTab === 'offers' ? 'border-indigo-600 text-indigo-650' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                <span>⚡</span> Top Announcement & Deals
            </button>
            <button 
                wire:click="$set('activeTab', 'analytics')"
                class="shrink-0 border-b-2 py-3 px-1 text-sm font-bold transition duration-150 {{ $activeTab === 'analytics' ? 'border-indigo-600 text-indigo-650' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                Analytics & Tracking
            </button>
        </nav>
    </div>

    @if($activeTab === 'offers')
        <form wire:submit="saveOffersSettings" class="space-y-6">
            <!-- Live Preview Banner Box -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Live Announcement Bar Preview</h3>
                    <span class="text-[10px] font-semibold text-slate-400">Shows atop all storefront pages</span>
                </div>
                
                @if($announcementEnabled)
                    <div class="rounded-xl bg-gradient-to-r from-indigo-700 via-purple-700 to-indigo-800 text-white text-xs py-2.5 px-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2 font-medium">
                            <span>{{ $announcementText ?: '⚡ Special Offer: Use Code SAVE10 for Extra 10% OFF + FREE Express Shipping!' }}</span>
                        </div>
                        @if($announcementCoupon)
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="bg-white/20 px-2.5 py-0.5 rounded-full font-mono font-extrabold uppercase text-[11px] border border-white/20">
                                    {{ $announcementCoupon }}
                                </span>
                                <span class="bg-white text-indigo-900 font-bold px-2.5 py-0.5 rounded-full text-[10px] uppercase shadow-xs">
                                    Copy Code
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-400 text-xs py-3 px-4 text-center italic">
                        Announcement Bar is currently disabled (hidden from storefront).
                    </div>
                @endif
            </div>

            <!-- Controls Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Toggle -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Enable Header Announcement Bar</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Toggle visibility of the top promo banner across the entire storefront.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="announcementEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Text Input -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Announcement Offer Text</label>
                    <input 
                        type="text" 
                        wire:model.live="announcementText" 
                        placeholder="e.g. ⚡ Flash Sale: Use Code SAVE10 for Extra 10% OFF + FREE Shipping!" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('announcementText') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- 2-Col Grid (Coupon + WhatsApp) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Promo / Coupon Code (Optional)</label>
                        <input 
                            type="text" 
                            wire:model.live="announcementCoupon" 
                            placeholder="e.g. SAVE10" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm uppercase font-mono text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        <p class="text-[11px] text-slate-400">Buyers can click 1 button to copy this coupon into their clipboard.</p>
                        @error('announcementCoupon') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">WhatsApp Store Order Number</label>
                        <input 
                            type="text" 
                            wire:model="whatsappNumber" 
                            placeholder="e.g. 919876543210" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        <p class="text-[11px] text-slate-400">Includes country code without + (e.g. 91 for India).</p>
                        @error('whatsappNumber') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Global Free Shipping Threshold -->
                <div class="space-y-2 border-t border-slate-100 pt-5">
                    <label class="block text-xs font-semibold text-slate-700">Global Free Shipping Threshold (₹)</label>
                    <div class="relative max-w-xs">
                        <span class="absolute left-3.5 top-2.5 text-xs font-bold text-slate-400">₹</span>
                        <input 
                            type="number"
                            step="1"
                            wire:model="globalFreeShippingThreshold"
                            placeholder="999"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 pl-8 pr-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                        />
                    </div>
                    <p class="text-[11px] text-slate-400">Orders above this value automatically unlock free shipping.</p>
                </div>
            </div>

            <!-- Footer Save -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                <span class="text-xs text-slate-500">Save changes to publish special offer updates immediately.</span>
                <button 
                    type="submit"
                    class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 text-xs font-bold text-white shadow transition"
                >
                    Save Special Offers Settings
                </button>
            </div>
        </form>
    @endif

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
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <span class="text-xs text-slate-500 font-medium">Add, customize, or delete homepage slider sections dynamically.</span>
                <button 
                    type="button"
                    wire:click="addCustomSlider"
                    class="rounded-xl border border-indigo-600 bg-indigo-50 text-indigo-750 px-3.5 py-2 text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Custom Slider Section
                </button>
            </div>

            <div class="space-y-6">
                @foreach($sliders as $idx => $slider)
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm relative">
                        <button 
                            type="button"
                            wire:click="deleteSlider({{ $idx }})"
                            wire:confirm="Are you sure you want to remove this slider section?"
                            class="absolute top-4 right-4 text-xs font-bold text-rose-600 hover:text-rose-700 transition"
                        >
                            &times; Delete Section
                        </button>

                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-indigo-650"></span>
                            Slider Section #{{ $idx + 1 }}: {{ $slider['title'] }}
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title & Subtitle -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Title</label>
                                    <input 
                                        type="text" 
                                        wire:model="sliders.{{ $idx }}.title" 
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                    />
                                    @error("sliders.{$idx}.title") <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Section Subtitle</label>
                                    <textarea 
                                        rows="2" 
                                        wire:model="sliders.{{ $idx }}.subtitle" 
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                    ></textarea>
                                    @error("sliders.{$idx}.subtitle") <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Query settings & items selection -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Display Limit</label>
                                        <input 
                                            type="number" 
                                            wire:model="sliders.{{ $idx }}.limit" 
                                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                        />
                                        @error("sliders.{$idx}.limit") <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Query Mode</label>
                                        <select 
                                            wire:model.live="sliders.{{ $idx }}.mode"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                                        >
                                            <option value="latest">Latest Products (Auto-query)</option>
                                            <option value="featured">Featured Status (Auto-query)</option>
                                            <option value="selected">Manual Selection</option>
                                        </select>
                                    </div>
                                </div>

                                @if($slider['mode'] === 'selected')
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-slate-500">Pick Products to Show</label>
                                        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-36 overflow-y-auto space-y-2">
                                            @foreach($this->allProducts as $p)
                                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                                    <input 
                                                        type="checkbox" 
                                                        value="{{ $p->id }}" 
                                                        wire:model="sliders.{{ $idx }}.product_ids" 
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
                    </div>
                @endforeach
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
    @elseif($activeTab === 'analytics')
        <form wire:submit="saveSliderSettings" class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Google Analytics (GA4) Integration</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Track website traffic, visitors, product views, and sales conversions automatically across all storefront pages.</p>
                </div>

                <div class="space-y-2 max-w-lg">
                    <label for="ga_id_input" class="block text-xs font-semibold text-slate-700">Google Analytics Measurement ID / Tag ID</label>
                    <input 
                        type="text"
                        id="ga_id_input"
                        wire:model="googleAnalyticsId"
                        placeholder="e.g. G-QNDMCPH3HH"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                    />
                    <p class="text-[11px] text-slate-500">Enter your GA4 Measurement ID (e.g. <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-600 font-mono">G-QNDMCPH3HH</code> or <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-600 font-mono">QNDMCPH3HH</code>). The tracking snippet will automatically load on all storefront pages.</p>
                    @error('googleAnalyticsId') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Global Free Shipping Threshold (₹)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Set the default minimum subtotal order amount required for customers to unlock free shipping across the store.</p>
                </div>

                <div class="space-y-2 max-w-lg">
                    <label for="shipping_threshold_input" class="block text-xs font-semibold text-slate-700">Default Free Shipping Goal (₹)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">₹</span>
                        <input 
                            type="number"
                            step="1"
                            id="shipping_threshold_input"
                            wire:model="globalFreeShippingThreshold"
                            placeholder="999"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 pl-8 pr-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                        />
                    </div>
                    <p class="text-[11px] text-slate-500">Individual products can override this threshold in their product settings.</p>
                    @error('globalFreeShippingThreshold') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Footer Save -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                <span class="text-xs text-slate-500">Save changes to update Google Analytics settings on the storefront.</span>
                <button 
                    type="submit"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:bg-indigo-500 transition"
                >
                    Save Analytics Settings
                </button>
            </div>
        </form>
    @endif
</div>
