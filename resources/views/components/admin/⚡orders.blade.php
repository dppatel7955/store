<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '']
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                      ->orWhere('payment_method', 'like', '%' . $this->search . '%')
                      ->orWhere('payment_status', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function($userQuery) {
                          $userQuery->where('name', 'like', '%' . $this->search . '%')
                                    ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest('id')
            ->paginate(10);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Orders</h1>
            <p class="text-xs text-slate-500 mt-1">Monitor customer transactions, invoices, and shipping statuses.</p>
        </div>
    </div>

    <!-- Toolbars / Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Search -->
        <div class="flex-1 max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search ID, customer name..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3">
            <select 
                wire:model.live="filterStatus" 
                class="bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 focus:outline-none focus:border-indigo-500"
            >
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Order ID</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Placed</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment Method</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Order Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Grand Total</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->orders as $order)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- Order ID -->
                            <td class="p-4 text-xs font-mono font-bold text-slate-900">#{{ $order->id }}</td>
                            <!-- Customer -->
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-800 leading-normal">{{ $order->user->name }}</span>
                                    <span class="text-[10px] text-slate-450">{{ $order->user->email }}</span>
                                </div>
                            </td>
                            <!-- Date Placed -->
                            <td class="p-4 text-xs text-slate-500">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                            <!-- Payment Method -->
                            <td class="p-4 text-xs text-slate-700 uppercase font-semibold">{{ $order->payment_method }}</td>
                            <!-- Payment Status -->
                            <td class="p-4">
                                @php
                                    $paymentColors = [
                                        'pending' => 'bg-amber-50 border border-amber-200 text-amber-700',
                                        'paid' => 'bg-emerald-50 border border-emerald-200 text-emerald-700',
                                        'failed' => 'bg-rose-50 border border-rose-200 text-rose-700',
                                    ];
                                    $pColor = $paymentColors[$order->payment_status] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $pColor }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>
                            <!-- Order Status -->
                            <td class="p-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-50 border border-amber-200 text-amber-700',
                                        'processing' => 'bg-blue-50 border border-blue-200 text-blue-700',
                                        'shipped' => 'bg-indigo-50 border border-indigo-200 text-indigo-700',
                                        'delivered' => 'bg-emerald-50 border border-emerald-200 text-emerald-700',
                                        'cancelled' => 'bg-rose-50 border border-rose-200 text-rose-700',
                                    ];
                                    $oColor = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $oColor }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <!-- Grand Total -->
                            <td class="p-4 text-xs font-black text-slate-900">₹{{ number_format($order->grand_total) }}</td>
                            <!-- Actions -->
                            <td class="p-4 text-right">
                                <a 
                                    href="/admin/orders/{{ $order->id }}" 
                                    class="text-indigo-605 hover:text-indigo-705 text-xs font-bold transition"
                                >
                                    View Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-xs text-slate-400 font-semibold">
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->orders->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->orders->links() }}
            </div>
        @endif
    </div>
</div>
