<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Coupon;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $isOpen = false;

    // Form fields
    public $couponId = null;
    public $code = '';
    public $type = 'fixed';
    public $value = '';
    public $min_order_amount = '';
    public $is_active = true;
    public $expires_at = '';
    public $user_id = null;
    public $brand_id = null;
    public $category_id = null;

    protected $queryString = [
        'search' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function coupons()
    {
        return Coupon::query()
            ->with(['user', 'brand', 'category'])
            ->when($this->search, function ($query) {
                $query->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('type', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function usersList()
    {
        return \App\Models\User::orderBy('name')->get();
    }

    #[Computed]
    public function brandsList()
    {
        return \App\Models\Brand::orderBy('name')->get();
    }

    #[Computed]
    public function categoriesList()
    {
        return \App\Models\Category::getTree();
    }

    public function openModal($id = null)
    {
        $this->resetErrorBag();

        if ($id) {
            $coupon = Coupon::findOrFail($id);
            $this->couponId = $coupon->id;
            $this->code = $coupon->code;
            $this->type = $coupon->type;
            $this->value = $coupon->value;
            $this->min_order_amount = $coupon->min_order_amount;
            $this->is_active = (bool) $coupon->is_active;
            $this->expires_at = $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '';
            $this->user_id = $coupon->user_id;
            $this->brand_id = $coupon->brand_id;
            $this->category_id = $coupon->category_id;
        } else {
            $this->resetFields();
        }

        $this->isOpen = true;
    }

    public function resetFields()
    {
        $this->couponId = null;
        $this->code = '';
        $this->type = 'fixed';
        $this->value = '';
        $this->min_order_amount = '';
        $this->is_active = true;
        $this->expires_at = '';
        $this->user_id = null;
        $this->brand_id = null;
        $this->category_id = null;
    }

    public function save()
    {
        $this->code = strtoupper(trim($this->code));

        $rules = [
            'code' => 'required|min:3|max:50|unique:coupons,code,' . ($this->couponId ?? 'NULL') . ',id',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'expires_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
        ];

        if ($this->type === 'percent') {
            $rules['value'] = 'required|numeric|min:0.01|max:100';
        }

        $this->validate($rules);

        Coupon::updateOrCreate(
            ['id' => $this->couponId],
            [
                'code' => $this->code,
                'type' => $this->type,
                'value' => $this->value,
                'min_order_amount' => $this->min_order_amount ?: null,
                'is_active' => $this->is_active,
                'expires_at' => $this->expires_at ? \Illuminate\Support\Carbon::parse($this->expires_at) : null,
                'user_id' => $this->user_id ?: null,
                'brand_id' => $this->brand_id ?: null,
                'category_id' => $this->category_id ?: null,
            ]
        );

        $this->isOpen = false;
        $this->dispatch('swal', title: 'Success!', text: 'Coupon saved successfully.', icon: 'success');
    }

    public function deleteCoupon($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        $this->dispatch('swal', title: 'Deleted!', text: 'Coupon deleted successfully.', icon: 'success');
    }

    public function toggleStatus($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();
        $this->dispatch('swal', title: 'Success!', text: 'Status updated successfully.', icon: 'success');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Coupons</h1>
            <p class="text-xs text-slate-500 mt-1">Manage promotional discount codes and coupons.</p>
        </div>
        <button 
            wire:click="openModal()"
            class="sm:self-center rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 px-4 text-xs font-bold text-white shadow-sm hover:shadow transition duration-150 flex items-center justify-center gap-1.5"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Coupon
        </button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="w-full sm:max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search by code..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2.5 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
            />
            <span class="absolute left-3 top-3 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Code</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Value</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Min Order</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Restrictions</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Expires At</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->coupons as $coupon)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-extrabold tracking-wider uppercase font-mono">
                                    {{ $coupon->code }}
                                </span>
                            </td>
                            <td class="p-4 text-xs text-slate-650 font-bold capitalize">
                                {{ $coupon->type }}
                            </td>
                            <td class="p-4 text-xs font-extrabold text-slate-800">
                                @if($coupon->type === 'percent')
                                    {{ number_format($coupon->value, 0) }}%
                                @else
                                    ₹{{ number_format($coupon->value, 2) }}
                                @endif
                            </td>
                            <td class="p-4 text-xs text-slate-600 font-semibold">
                                @if($coupon->min_order_amount)
                                    ₹{{ number_format($coupon->min_order_amount, 2) }}
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-slate-600 font-semibold">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($coupon->user)
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.5 rounded text-[9px] font-bold">
                                            User: {{ $coupon->user->name }}
                                        </span>
                                    @endif
                                    @if($coupon->brand)
                                        <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-800 border border-blue-200 px-1.5 py-0.5 rounded text-[9px] font-bold">
                                            Brand: {{ $coupon->brand->name }}
                                        </span>
                                    @endif
                                    @if($coupon->category)
                                        <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-800 border border-purple-200 px-1.5 py-0.5 rounded text-[9px] font-bold">
                                            Category: {{ $coupon->category->name }}
                                        </span>
                                    @endif
                                    @if(!$coupon->user_id && !$coupon->brand_id && !$coupon->category_id)
                                        <span class="text-slate-400 text-xs">None</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-xs text-slate-600 font-semibold">
                                @if($coupon->expires_at)
                                    <span class="{{ $coupon->isExpired() ? 'text-rose-600 font-bold' : 'text-slate-600' }}">
                                        {{ $coupon->expires_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Never</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <button 
                                    wire:click="toggleStatus({{ $coupon->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-250 hover:bg-emerald-100' : 'bg-slate-100 text-slate-650 hover:bg-slate-150' }}"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full {{ $coupon->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2.5">
                                    <button 
                                        wire:click="openModal({{ $coupon->id }})"
                                        class="text-indigo-650 hover:text-indigo-700 text-xs font-bold transition"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        wire:confirm="Are you sure you want to delete this coupon?"
                                        wire:click="deleteCoupon({{ $coupon->id }})"
                                        class="text-rose-650 hover:text-rose-700 text-xs font-bold transition"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-xs text-slate-500 font-medium">No coupons found. Create your first coupon to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->coupons->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $this->coupons->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition duration-300">
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 bg-slate-50/50 py-4 px-6 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-sm">{{ $couponId ? 'Edit Coupon' : 'Create New Coupon' }}</h3>
                    <button wire:click="$set('isOpen', false)" class="text-slate-450 hover:text-slate-600 transition text-lg leading-none">&times;</button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <!-- Code -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Coupon Code <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            wire:model="code" 
                            placeholder="e.g. SAVE20" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition font-mono uppercase"
                        />
                        @error('code') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Discount Type</label>
                        <select 
                            wire:model.live="type"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="fixed">Fixed Amount (₹)</option>
                            <option value="percent">Percentage Discount (%)</option>
                        </select>
                        @error('type') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Value -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">
                            Discount Value {{ $type === 'percent' ? '(%)' : '(₹)' }} <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01" 
                            wire:model="value" 
                            placeholder="{{ $type === 'percent' ? 'e.g. 20' : 'e.g. 150' }}" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('value') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Min Order Amount -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Minimum Order Amount (Optional, ₹)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            wire:model="min_order_amount" 
                            placeholder="e.g. 500" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('min_order_amount') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Expires At -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Expiry Date (Optional)</label>
                        <input 
                            type="date" 
                            wire:model="expires_at" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        @error('expires_at') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- User Restriction -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Restrict to User (Optional)</label>
                        <select 
                            wire:model="user_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="">-- All Users --</option>
                            @foreach($this->usersList as $usr)
                                <option value="{{ $usr->id }}">{{ $usr->name }} ({{ $usr->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Brand Restriction -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Restrict to Brand (Optional)</label>
                        <select 
                            wire:model="brand_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="">-- All Brands --</option>
                            @foreach($this->brandsList as $brnd)
                                <option value="{{ $brnd->id }}">{{ $brnd->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category Restriction -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Restrict to Category (Optional)</label>
                        <select 
                            wire:model="category_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="">-- All Categories --</option>
                            @foreach($this->categoriesList as $cat)
                                <option value="{{ $cat->id }}">{{ str_repeat('— ', $cat->depth) }}{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                wire:model="is_active" 
                                class="rounded border-slate-350 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                            />
                            <span class="text-xs font-bold text-slate-750">Active & Redeemable</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 bg-slate-50/50 -mx-6 -mb-6 px-6 py-4">
                        <button 
                            type="button" 
                            wire:click="$set('isOpen', false)"
                            class="rounded-xl border border-slate-200 bg-white text-slate-700 px-4 py-2 text-xs font-bold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="rounded-xl bg-indigo-600 text-white px-4 py-2 text-xs font-bold hover:bg-indigo-500 transition"
                        >
                            Save Coupon
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
