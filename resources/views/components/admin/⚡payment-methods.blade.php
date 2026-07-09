<?php

use App\Models\Order;
use App\Models\PaymentMethod;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $isOpen = false;
    public ?int $paymentMethodId = null;
    public string $name = '';
    public string $code = '';
    public string $handler = 'offline';
    public string $gateway_key = '';
    public string $gateway_secret = '';
    public string $description = '';
    public string $instructions = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);
    }

    public function openModal(?int $id = null): void
    {
        $this->resetErrorBag();

        if ($id) {
            $method = PaymentMethod::findOrFail($id);
            $this->paymentMethodId = $method->id;
            $this->name = $method->name;
            $this->code = $method->code;
            $this->handler = $method->handler;
            $this->gateway_key = (string) ($method->gateway_key ?? '');
            $this->gateway_secret = (string) ($method->gateway_secret ?? '');
            $this->description = (string) $method->description;
            $this->instructions = (string) $method->instructions;
            $this->sort_order = (int) $method->sort_order;
            $this->is_active = (bool) $method->is_active;
        } else {
            $this->resetFields();
        }

        $this->isOpen = true;
    }

    public function resetFields(): void
    {
        $this->paymentMethodId = null;
        $this->name = '';
        $this->code = '';
        $this->handler = 'offline';
        $this->gateway_key = '';
        $this->gateway_secret = '';
        $this->description = '';
        $this->instructions = '';
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function save(): void
    {
        $this->code = strtolower(trim($this->code));

        $this->validate([
            'name' => 'required|string|min:2|max:80',
            'code' => 'required|alpha_dash|min:2|max:40|unique:payment_methods,code,' . ($this->paymentMethodId ?? 'NULL') . ',id',
            'handler' => 'required|in:offline,razorpay',
            'gateway_key' => 'nullable|string|max:255',
            'gateway_secret' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'instructions' => 'nullable|string|max:1000',
            'sort_order' => 'required|integer|min:0|max:9999',
            'is_active' => 'required|boolean',
        ]);

        if ($this->handler === 'razorpay') {
            $this->validate([
                'gateway_key' => 'required|string|max:255',
                'gateway_secret' => 'required|string|max:255',
            ]);
        }

        PaymentMethod::updateOrCreate(
            ['id' => $this->paymentMethodId],
            [
                'name' => trim($this->name),
                'code' => $this->code,
                'handler' => $this->handler,
                'gateway_key' => trim($this->gateway_key) ?: null,
                'gateway_secret' => trim($this->gateway_secret) ?: null,
                'description' => trim($this->description) ?: null,
                'instructions' => trim($this->instructions) ?: null,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ]
        );

        $this->isOpen = false;
        $this->dispatch('swal', title: 'Saved!', text: 'Payment method saved successfully.', icon: 'success');
    }

    public function toggleStatus(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $method->is_active = !$method->is_active;
        $method->save();

        $this->dispatch('swal', title: 'Updated!', text: 'Payment method status updated.', icon: 'success');
    }

    public function deleteMethod(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $hasOrders = Order::where('payment_method', $method->code)->exists();

        if ($hasOrders) {
            $this->dispatch('swal', title: 'Blocked', text: 'Cannot delete a method used by orders. Set it inactive instead.', icon: 'warning');
            return;
        }

        $method->delete();
        $this->dispatch('swal', title: 'Deleted!', text: 'Payment method removed.', icon: 'success');
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Payment Methods</h1>
            <p class="text-xs text-slate-500 mt-1">Add, configure and activate payment methods for checkout.</p>
        </div>
        <button
            wire:click="openModal"
            class="sm:self-center rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 px-4 text-xs font-bold text-white shadow-sm hover:shadow transition duration-150"
        >
            Add Payment Method
        </button>
    </div>

    <div class="w-full sm:max-w-xs relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search name or code..."
            class="w-full bg-white border border-slate-200 rounded-xl py-2.5 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
        />
        <span class="absolute left-3 top-3 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </span>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Code</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Handler</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Credentials</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Sort</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->paymentMethods as $method)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="p-4">
                                <div class="text-sm font-bold text-slate-800">{{ $method->name }}</div>
                                @if($method->description)
                                    <div class="text-xs text-slate-500 mt-1">{{ $method->description }}</div>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-extrabold tracking-wider uppercase font-mono">
                                    {{ $method->code }}
                                </span>
                            </td>
                            <td class="p-4 text-xs font-semibold text-slate-600 uppercase">{{ $method->handler }}</td>
                            <td class="p-4">
                                @if($method->handler === 'razorpay')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold {{ $method->gateway_key && $method->gateway_secret ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $method->gateway_key && $method->gateway_secret ? 'Configured' : 'Missing Keys' }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-500 font-semibold">N/A</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs font-semibold text-slate-700">{{ $method->sort_order }}</td>
                            <td class="p-4">
                                <button
                                    wire:click="toggleStatus({{ $method->id }})"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}"
                                >
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="p-4 text-right space-x-1.5">
                                <button wire:click="openModal({{ $method->id }})" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-150 hover:bg-indigo-100 transition">
                                    Edit
                                </button>
                                <button wire:click="deleteMethod({{ $method->id }})" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-150 hover:bg-rose-100 transition">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-xs text-slate-500">No payment methods found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50/50">
            {{ $this->paymentMethods->links() }}
        </div>
    </div>

    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/40" wire:click="$set('isOpen', false)"></div>
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-lg font-extrabold text-slate-900">{{ $paymentMethodId ? 'Edit Payment Method' : 'Create Payment Method' }}</h2>
                        <button wire:click="$set('isOpen', false)" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Name</label>
                            <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Code (unique)</label>
                            <input type="text" wire:model="code" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('code') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Handler</label>
                            <select wire:model="handler" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="offline">Offline</option>
                                <option value="razorpay">Razorpay</option>
                            </select>
                            @error('handler') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('sort_order') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($handler === 'razorpay')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Gateway Key ID</label>
                                <input type="text" wire:model="gateway_key" autocomplete="off" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @error('gateway_key') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Gateway Secret Key</label>
                                <input type="password" wire:model="gateway_secret" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @error('gateway_secret') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
                        @error('description') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Instructions (optional)</label>
                        <textarea wire:model="instructions" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
                        @error('instructions') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Active on frontend checkout
                    </label>

                    <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                        <button wire:click="$set('isOpen', false)" class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button wire:click="save" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500">Save Method</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
