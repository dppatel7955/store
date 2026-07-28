<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ShippingZone;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public bool $isOpen = false;

    // Form fields
    public $editingId = null;
    public string $name = '';
    public string $type = 'city'; // 'city', 'pincode', 'state', 'default'
    public string $locationsInput = ''; // Comma or newline separated
    public $shipping_charge = 50.00;
    public $free_shipping_threshold = null;
    public string $estimated_delivery_days = '2-4 Business Days';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModal($id = null)
    {
        $this->resetValidation();

        if ($id) {
            $zone = ShippingZone::findOrFail($id);
            $this->editingId = $zone->id;
            $this->name = $zone->name;
            $this->type = $zone->type;
            $this->locationsInput = is_array($zone->locations) ? implode(', ', $zone->locations) : '';
            $this->shipping_charge = $zone->shipping_charge;
            $this->free_shipping_threshold = $zone->free_shipping_threshold;
            $this->estimated_delivery_days = $zone->estimated_delivery_days ?? '2-4 Business Days';
            $this->sort_order = $zone->sort_order;
            $this->is_active = (bool) $zone->is_active;
        } else {
            $this->editingId = null;
            $this->name = '';
            $this->type = 'city';
            $this->locationsInput = '';
            $this->shipping_charge = 50.00;
            $this->free_shipping_threshold = null;
            $this->estimated_delivery_days = '2-4 Business Days';
            $this->sort_order = 0;
            $this->is_active = true;
        }

        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function addPreset($presetType)
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->is_active = true;

        if ($presetType === 'mumbai') {
            $this->name = 'Mumbai & Local Zone';
            $this->type = 'city';
            $this->locationsInput = 'Mumbai, Thane, Navi Mumbai, Kalyan';
            $this->shipping_charge = 40.00;
            $this->free_shipping_threshold = 799.00;
            $this->estimated_delivery_days = '1-2 Days (Express)';
            $this->sort_order = 1;
        } elseif ($presetType === 'delhi') {
            $this->name = 'Delhi / NCR Zone';
            $this->type = 'city';
            $this->locationsInput = 'Delhi, New Delhi, Noida, Gurgaon, Ghaziabad';
            $this->shipping_charge = 60.00;
            $this->free_shipping_threshold = 999.00;
            $this->estimated_delivery_days = '2-3 Days';
            $this->sort_order = 2;
        } elseif ($presetType === 'bangalore') {
            $this->name = 'Bangalore & South Metros';
            $this->type = 'city';
            $this->locationsInput = 'Bangalore, Bengaluru, Chennai, Hyderabad';
            $this->shipping_charge = 70.00;
            $this->free_shipping_threshold = 999.00;
            $this->estimated_delivery_days = '2-4 Days';
            $this->sort_order = 3;
        } elseif ($presetType === 'default') {
            $this->name = 'Rest of India (Default)';
            $this->type = 'default';
            $this->locationsInput = '';
            $this->shipping_charge = 100.00;
            $this->free_shipping_threshold = 999.00;
            $this->estimated_delivery_days = '3-5 Days';
            $this->sort_order = 99;
        }

        $this->isOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:city,pincode,state,default',
            'locationsInput' => 'nullable|string|max:2000',
            'shipping_charge' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_delivery_days' => 'nullable|string|max:100',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        // Clean locations list
        $locations = [];
        if ($this->type !== 'default' && ! empty(trim($this->locationsInput))) {
            $locations = array_values(array_filter(array_map('trim', explode(',', str_replace(["\r\n", "\n", "\r"], ',', $this->locationsInput)))));
        }

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'locations' => $locations,
            'shipping_charge' => $this->shipping_charge,
            'free_shipping_threshold' => $this->free_shipping_threshold !== '' ? $this->free_shipping_threshold : null,
            'estimated_delivery_days' => $this->estimated_delivery_days ?: '2-4 Business Days',
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $zone = ShippingZone::findOrFail($this->editingId);
            $zone->update($data);
            $this->dispatch('swal', title: 'Saved!', text: 'Shipping zone updated successfully.', icon: 'success');
        } else {
            ShippingZone::create($data);
            $this->dispatch('swal', title: 'Created!', text: 'New shipping zone added successfully.', icon: 'success');
        }

        \App\Services\ShippingService::clearCache();
        $this->isOpen = false;
    }

    public function toggleActive($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->update(['is_active' => !$zone->is_active]);
        \App\Services\ShippingService::clearCache();
        $this->dispatch('swal', title: 'Status Changed!', text: 'Shipping zone status updated.', icon: 'info');
    }

    public function deleteZone($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->delete();
        \App\Services\ShippingService::clearCache();
        $this->dispatch('swal', title: 'Deleted!', text: 'Shipping zone removed.', icon: 'success');
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('type', 'like', '%' . $this->search . '%')
                    ->orWhere('locations', 'like', '%' . $this->search . '%');
            })
            ->orderBy('sort_order', 'asc')
            ->paginate(10);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Location Shipping Rates</h1>
            <p class="text-xs text-slate-500 mt-1">Configure regional shipping rates, delivery timeframes, and free shipping thresholds.</p>
        </div>
        <button 
            wire:click="openModal()"
            class="rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 px-4 text-xs font-bold text-white shadow-sm hover:shadow transition flex items-center justify-center gap-1.5"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Location Shipping Zone
        </button>
    </div>

    <!-- Quick Presets -->
    <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs font-bold text-indigo-900">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Popular Regional Presets:
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="addPreset('mumbai')" type="button" class="rounded-lg bg-white border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-xs">+ Mumbai Metro</button>
            <button wire:click="addPreset('delhi')" type="button" class="rounded-lg bg-white border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-xs">+ Delhi NCR</button>
            <button wire:click="addPreset('bangalore')" type="button" class="rounded-lg bg-white border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-xs">+ South Metros</button>
            <button wire:click="addPreset('default')" type="button" class="rounded-lg bg-white border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition shadow-xs">+ Rest of India</button>
        </div>
    </div>

    <!-- Toolbar & Search -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search zones by city, pincode, state or name..." 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
            />
            <svg class="h-4 w-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <!-- Table List -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-4">Priority</th>
                        <th class="py-3.5 px-4">Zone Name & Type</th>
                        <th class="py-3.5 px-4">Matched Locations</th>
                        <th class="py-3.5 px-4">Shipping Charge</th>
                        <th class="py-3.5 px-4">Free Shipping Goal</th>
                        <th class="py-3.5 px-4">Est. Delivery Time</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($this->zones as $z)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4 font-mono text-slate-400 font-bold">#{{ $z->sort_order }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <div>{{ $z->name }}</div>
                                <span class="inline-block mt-0.5 text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $z->type === 'default' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                    {{ $z->type }} match
                                </span>
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                @if(is_array($z->locations) && count($z->locations) > 0)
                                    <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto">
                                        @foreach($z->locations as $loc)
                                            <span class="bg-slate-100 text-slate-700 text-[10px] font-semibold px-2 py-0.5 rounded border border-slate-200">{{ $loc }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">All Fallback Locations</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-extrabold text-slate-900">₹{{ number_format($z->shipping_charge, 2) }}</td>
                            <td class="py-3.5 px-4">
                                @if(!is_null($z->free_shipping_threshold))
                                    <span class="text-emerald-700 font-bold">₹{{ number_format($z->free_shipping_threshold) }}</span>
                                @else
                                    <span class="text-slate-400">Store Default</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">
                                {{ $z->estimated_delivery_days ?: '2-4 Business Days' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <button 
                                    wire:click="toggleActive({{ $z->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition {{ $z->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full {{ $z->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $z->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <button wire:click="openModal({{ $z->id }})" class="font-bold text-indigo-600 hover:text-indigo-800">Edit</button>
                                <button wire:click="deleteZone({{ $z->id }})" wire:confirm="Delete this shipping zone?" class="font-bold text-rose-600 hover:text-rose-800">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-medium">No shipping zones found matching your search. Click 'Add Location Shipping Zone' above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->zones->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $this->zones->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition">
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 bg-slate-50/50 py-4 px-6 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">{{ $editingId ? 'Edit Shipping Zone' : 'Create Location Shipping Zone' }}</h3>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-slate-600 transition text-lg leading-none">&times;</button>
                </div>

                <!-- Modal Body -->
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Zone Name <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            wire:model="name"
                            placeholder="e.g. Mumbai & Suburban Zone"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                        />
                        @error('name') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Zone Match Type <span class="text-rose-500">*</span></label>
                        <select 
                            wire:model.live="type"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                        >
                            <option value="city">City Names (e.g. Mumbai, Delhi, Bangalore)</option>
                            <option value="pincode">Postal Pincodes (e.g. 400001, 400002)</option>
                            <option value="state">State Names (e.g. Maharashtra, Gujarat)</option>
                            <option value="default">Default Region (Rest of India)</option>
                        </select>
                        @error('type') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($type !== 'default')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Locations List <span class="text-slate-400 font-normal">(Comma separated)</span>
                            </label>
                            <textarea 
                                wire:model="locationsInput"
                                rows="3"
                                placeholder="e.g. Mumbai, Thane, Navi Mumbai, Kalyan"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                            ></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Enter target cities, area names, or pincodes separated by commas.</p>
                            @error('locationsInput') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Shipping Charge (₹) <span class="text-rose-500">*</span></label>
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model="shipping_charge"
                                placeholder="40.00"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                            />
                            @error('shipping_charge') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Free Shipping Goal (₹) <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model="free_shipping_threshold"
                                placeholder="Leave blank for default"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                            />
                            @error('free_shipping_threshold') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Estimated Delivery Timeframe</label>
                        <input 
                            type="text" 
                            wire:model="estimated_delivery_days"
                            placeholder="e.g. 1-2 Days (Express) or 2-4 Business Days"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Displayed on product pages, cart drawer, and checkout.</p>
                        @error('estimated_delivery_days') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Order Priority</label>
                            <input 
                                type="number" 
                                wire:model="sort_order"
                                placeholder="0"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                            />
                            @error('sort_order') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="is_active" class="rounded text-indigo-600 focus:ring-indigo-600 border-slate-300">
                                <span class="text-xs font-semibold text-slate-700">Active Zone Status</span>
                            </label>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button type="button" wire:click="closeModal" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow hover:bg-indigo-500 transition">
                            {{ $editingId ? 'Save Changes' : 'Create Zone' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
