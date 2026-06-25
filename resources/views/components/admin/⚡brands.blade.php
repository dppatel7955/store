<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $isOpen = false;

    // Form fields
    public $brandId = null;
    public $name = '';
    public $slug = '';
    public $logo = '';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedName($value)
    {
        if (!$this->brandId) {
            $this->slug = Str::slug($value);
        }
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->paginate(8);
    }

    public function openModal($id = null)
    {
        $this->resetErrorBag();

        if ($id) {
            $brand = Brand::findOrFail($id);
            $this->brandId = $brand->id;
            $this->name = $brand->name;
            $this->slug = $brand->slug;
            $this->logo = $brand->logo;
            $this->is_active = (bool) $brand->is_active;
        } else {
            $this->resetFields();
        }

        $this->isOpen = true;
    }

    public function resetFields()
    {
        $this->brandId = null;
        $this->name = '';
        $this->slug = '';
        $this->logo = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=100&auto=format&fit=crop';
        $this->is_active = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:2|max:255',
            'slug' => 'required|max:255|unique:brands,slug,' . ($this->brandId ?? 'NULL') . ',id',
            'logo' => 'required|url',
            'is_active' => 'required|boolean'
        ]);

        Brand::updateOrCreate(
            ['id' => $this->brandId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'logo' => $this->logo,
                'is_active' => $this->is_active
            ]
        );

        session()->flash('success', $this->brandId ? 'Brand updated successfully.' : 'Brand created successfully.');
        $this->isOpen = false;
        $this->resetFields();
    }

    public function toggleStatus($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->is_active = !$brand->is_active;
        $brand->save();
        session()->flash('success', 'Status updated successfully.');
    }

    public function delete($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->count() > 0) {
            session()->flash('error', "Cannot delete brand '{$brand->name}' because it contains products.");
            return;
        }

        $brand->delete();
        session()->flash('success', 'Brand deleted successfully.');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Brands</h1>
            <p class="text-xs text-slate-500 mt-1">Manage manufacturers and product brands.</p>
        </div>
        <button 
            wire:click="openModal()" 
            class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
        >
            Add Brand
        </button>
    </div>

    <!-- Status Messages -->
    @if (session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex-1 max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search brands..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-500">
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
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Logo</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Slug</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->brands as $brand)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- Logo -->
                            <td class="p-4">
                                <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" class="h-10 w-10 object-contain rounded-lg border border-slate-200 p-1 bg-white">
                            </td>
                            <!-- Name -->
                            <td class="p-4 text-xs font-bold text-slate-800">{{ $brand->name }}</td>
                            <!-- Slug -->
                            <td class="p-4 text-xs text-slate-500 font-mono">{{ $brand->slug }}</td>
                            <!-- Status -->
                            <td class="p-4">
                                <button 
                                    wire:click="toggleStatus({{ $brand->id }})"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $brand->is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $brand->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <!-- Actions -->
                            <td class="p-4 text-right space-x-2">
                                <button 
                                    wire:click="openModal({{ $brand->id }})" 
                                    class="text-indigo-605 hover:text-indigo-700 text-xs font-bold transition"
                                >
                                    Edit
                                </button>
                                <button 
                                    wire:confirm="Are you sure you want to delete this brand?"
                                    wire:click="delete({{ $brand->id }})" 
                                    class="text-rose-605 hover:text-rose-700 text-xs font-bold transition"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-xs text-slate-400 font-semibold">
                                No brands found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->brands->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->brands->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    <div 
        x-data="{ show: @entangle('isOpen') }" 
        x-show="show" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" 
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="show = false" 
            class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"
        ></div>

        <!-- Modal panel -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-2xl transition-all"
        >
            <div class="px-6 py-6 bg-white border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900">
                    {{ $brandId ? 'Edit Brand' : 'Create Brand' }}
                </h3>
                <button @click="show = false" class="text-slate-500 hover:text-slate-800 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Brand Name</label>
                    <input 
                        type="text" 
                        wire:model.live="name" 
                        placeholder="e.g. Apple" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Slug</label>
                    <input 
                        type="text" 
                        wire:model="slug" 
                        placeholder="apple" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition font-mono"
                    />
                    @error('slug') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Logo URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Logo URL</label>
                    <input 
                        type="text" 
                        wire:model.live="logo" 
                        placeholder="https://logo.clearbit.com/apple.com" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-655 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('logo') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    
                    @if($logo && !filter_var($logo, FILTER_VALIDATE_URL) === false)
                        <div class="mt-2 text-center">
                            <span class="text-[10px] text-slate-450 block mb-1">Logo Preview</span>
                            <img src="{{ $logo }}" class="h-16 w-16 object-contain rounded-lg border border-slate-200 p-1 mx-auto bg-white" onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=100&auto=format&fit=crop'">
                        </div>
                    @endif
                </div>

                <!-- Status Toggle -->
                <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                    <span class="text-xs font-semibold text-slate-705">Active Status</span>
                    <button 
                        type="button"
                        wire:click="$toggle('is_active')"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                    >
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <!-- Footer buttons -->
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 mt-6">
                    <button 
                        type="button" 
                        @click="show = false" 
                        class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-707 hover:bg-slate-200 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
