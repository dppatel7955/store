{{-- Shared shop filter fields for desktop sidebar + mobile drawer --}}
<div class="space-y-6">
    <div class="flex items-center justify-between {{ ($compact ?? false) ? 'hidden' : '' }}">
        <h3 class="font-bold text-slate-900">Filters</h3>
        <button type="button" wire:click="resetFilters" class="text-xs text-indigo-600 hover:text-indigo-500 font-semibold transition">
            Reset All
        </button>
    </div>

    <!-- Categories -->
    <div>
        <h4 class="text-sm font-bold text-slate-800 mb-3">Categories</h4>
        <div class="space-y-1 max-h-64 overflow-y-auto overscroll-contain pr-1">
            @foreach($categoriesList as $cat)
                <div class="space-y-1">
                    <label class="flex items-center gap-3 min-h-11 px-1 rounded-lg text-sm text-slate-800 font-semibold hover:bg-slate-50 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            value="{{ $cat->id }}"
                            wire:model.live="selectedCategories"
                            class="h-4 w-4 rounded border-slate-300 bg-white text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="flex-1">{{ $cat->name }}</span>
                    </label>
                    @if($cat->children->isNotEmpty())
                        <div class="pl-4 space-y-0.5 border-l border-slate-100 ml-3">
                            @foreach($cat->children as $child)
                                <label class="flex items-center gap-2.5 min-h-10 px-1 rounded-lg text-sm text-slate-600 hover:bg-slate-50 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        value="{{ $child->id }}"
                                        wire:model.live="selectedCategories"
                                        class="h-4 w-4 rounded border-slate-300 bg-white text-indigo-600 focus:ring-indigo-500"
                                    />
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
        <div class="space-y-0.5 max-h-48 overflow-y-auto overscroll-contain pr-1">
            @foreach($brandsList as $brand)
                <label class="flex items-center gap-3 min-h-11 px-1 rounded-lg text-sm text-slate-700 hover:bg-slate-50 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        value="{{ $brand->id }}"
                        wire:model.live="selectedBrands"
                        class="h-4 w-4 rounded border-slate-300 bg-white text-indigo-600 focus:ring-indigo-500"
                    />
                    <span>{{ $brand->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Price Range -->
    <div>
        <h4 class="text-sm font-bold text-slate-800 mb-3">Price Range (₹)</h4>
        <div class="flex items-center gap-2">
            <input
                type="number"
                wire:model.live.debounce.500ms="minPrice"
                placeholder="Min"
                inputmode="numeric"
                class="w-full min-h-11 bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-700 focus:outline-none focus:border-indigo-600"
            />
            <span class="text-slate-500 text-sm shrink-0">to</span>
            <input
                type="number"
                wire:model.live.debounce.500ms="maxPrice"
                placeholder="Max"
                inputmode="numeric"
                class="w-full min-h-11 bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-700 focus:outline-none focus:border-indigo-600"
            />
        </div>
    </div>
</div>
