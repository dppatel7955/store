<?php

use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $badge_text = 'Premium Hardware & Tech';
    public string $banner_title = 'Unleash the Future of Digital Shopping';
    public string $banner_subtitle = 'Explore handpicked flagship smartphones, laptops, smartwatches, and accessories with lightning-fast checkout.';
    public string $banner_image = '';
    public $bannerImageFile = null;

    public function mount()
    {
        if (file_exists(storage_path('app/home_settings.json'))) {
            $settings = json_decode(file_get_contents(storage_path('app/home_settings.json')), true);
            if ($settings) {
                $this->badge_text = $settings['badge_text'] ?? 'Premium Hardware & Tech';
                $this->banner_title = $settings['banner_title'] ?? 'Unleash the Future of Digital Shopping';
                $this->banner_subtitle = $settings['banner_subtitle'] ?? 'Explore handpicked flagship smartphones, laptops, smartwatches, and accessories with lightning-fast checkout.';
                $this->banner_image = $settings['banner_image'] ?? '';
            }
        }
    }

    public function save()
    {
        $this->validate([
            'badge_text' => 'required|string|max:100',
            'banner_title' => 'required|string|max:255',
            'banner_subtitle' => 'required|string|max:1000',
            'bannerImageFile' => 'nullable|image|max:3072',
        ]);

        if ($this->bannerImageFile) {
            $this->banner_image = '/storage/' . $this->bannerImageFile->store('banners', 'public');
            $this->bannerImageFile = null; // Reset temp upload file
        }

        $settings = [
            'badge_text' => $this->badge_text,
            'banner_title' => $this->banner_title,
            'banner_subtitle' => $this->banner_subtitle,
            'banner_image' => $this->banner_image,
        ];

        file_put_contents(storage_path('app/home_settings.json'), json_encode($settings, JSON_PRETTY_PRINT));

        $this->dispatch('swal', title: 'Success!', text: 'Homepage banner settings saved successfully.', icon: 'success');
    }

    public function clearBannerImage()
    {
        $this->banner_image = '';
        $this->bannerImageFile = null;
        $this->save();
    }
};
?>

<div class="space-y-6 max-w-3xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Home Settings</h1>
            <p class="text-xs text-slate-500 mt-1">Customize the storefront hero section text and banner media.</p>
        </div>
    </div>

    <!-- Configuration Panel -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <form wire:submit="save" class="space-y-6">
            
            <!-- Badge Text -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">Hero Badge Text</label>
                <input 
                    type="text" 
                    wire:model="badge_text" 
                    placeholder="e.g. Premium Hardware & Tech" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                />
                @error('badge_text') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <!-- Banner Title -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">Hero Banner Title</label>
                <input 
                    type="text" 
                    wire:model="banner_title" 
                    placeholder="e.g. Unleash the Future of Digital Shopping" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition font-bold"
                />
                @error('banner_title') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <!-- Banner Subtitle -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">Hero Banner Subtitle</label>
                <textarea 
                    wire:model="banner_subtitle" 
                    rows="3" 
                    placeholder="e.g. Explore handpicked flagship smartphones..." 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-xs text-slate-850 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition leading-relaxed"
                ></textarea>
                @error('banner_subtitle') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <!-- Image File Upload -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">Hero Banner Image (Optional)</label>
                <div class="flex items-center gap-4">
                    @if($bannerImageFile)
                        <div class="relative h-20 w-32 rounded-xl overflow-hidden border border-indigo-200 bg-slate-50 shadow-sm flex-shrink-0">
                            <img src="{{ $bannerImageFile->temporaryUrl() }}" alt="Banner preview" class="h-full w-full object-cover">
                        </div>
                    @elseif($banner_image)
                        <div class="relative h-20 w-32 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm flex-shrink-0 group">
                            <img src="{{ $banner_image }}" alt="Hero banner" class="h-full w-full object-cover">
                            <button 
                                type="button" 
                                wire:click="clearBannerImage" 
                                class="absolute inset-0 bg-slate-900/60 flex items-center justify-center text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition"
                            >
                                Clear Image
                            </button>
                        </div>
                    @else
                        <div class="h-20 w-32 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 flex items-center justify-center text-[10px] text-slate-400 font-medium flex-shrink-0">
                            No Banner Image
                        </div>
                    @endif
                    
                    <div class="flex-1">
                        <label class="flex flex-col items-center justify-center w-full h-20 border-2 border-slate-200 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/50 transition">
                            <div class="flex flex-col items-center justify-center pt-2.5 pb-2.5">
                                <svg class="w-6 h-6 text-slate-450 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-[10px] text-slate-500 font-bold">Upload banner image</p>
                            </div>
                            <input type="file" wire:model="bannerImageFile" accept="image/*" class="hidden" />
                        </label>
                    </div>
                </div>
                @error('bannerImageFile') <span class="text-[10px] text-rose-600 font-semibold block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <!-- Action buttons -->
            <div class="border-t border-slate-200 pt-5 flex items-center justify-end">
                <button 
                    type="submit"
                    class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
                >
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
