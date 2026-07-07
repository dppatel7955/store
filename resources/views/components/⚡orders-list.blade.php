<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    #[Computed]
    public function orders()
    {
        return Order::where('user_id', auth()->id())
            ->latest('id')
            ->paginate(10);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900">My Orders</h1>
        <p class="text-sm text-slate-500 mt-1">Track your order statuses, shipping updates, and invoice receipts.</p>
    </div>

    @if($this->orders->count() > 0)
        <!-- Orders Card / List -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Order ID</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Placed</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment Method</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment Status</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Order Status</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-250/60 text-xs">
                        @foreach($this->orders as $order)
                            <tr class="hover:bg-slate-50/40 transition duration-150">
                                <!-- Order ID -->
                                <td class="p-4 font-mono font-bold text-slate-900">#{{ $order->id }}</td>
                                
                                <!-- Date Placed -->
                                <td class="p-4 text-slate-600 font-medium">
                                    {{ $order->created_at->format('M d, Y h:i A') }}
                                </td>
                                
                                <!-- Payment Method -->
                                <td class="p-4 text-slate-600 uppercase font-semibold">
                                    {{ $order->payment_method }}
                                </td>
                                
                                <!-- Payment Status -->
                                <td class="p-4">
                                    @if($order->payment_status === 'paid')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                            Paid
                                        </span>
                                    @elseif($order->payment_status === 'pending')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Order Status -->
                                <td class="p-4">
                                    @if($order->status === 'delivered')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                            Delivered
                                        </span>
                                    @elseif($order->status === 'shipped')
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-indigo-700 ring-1 ring-inset ring-indigo-600/10">
                                            Shipped
                                        </span>
                                    @elseif($order->status === 'processing')
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-blue-700 ring-1 ring-inset ring-blue-600/10">
                                            Processing
                                        </span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase text-slate-705 ring-1 ring-inset ring-slate-600/10">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-amber-750 ring-1 ring-inset ring-amber-600/10">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Total Amount -->
                                <td class="p-4 font-bold text-slate-900">
                                    ₹{{ number_format($order->grand_total) }}
                                </td>
                                
                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <a 
                                        href="/orders/{{ $order->id }}"
                                        class="inline-flex items-center rounded-xl bg-slate-100 border border-slate-200 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-650 transition duration-150"
                                    >
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            @if($this->orders->hasPages())
                <div class="px-4 py-4 border-t border-slate-200 bg-slate-50/50">
                    {{ $this->orders->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
            <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-indigo-50 text-indigo-600 mb-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-805">No orders yet</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto leading-relaxed">You haven\'t placed any purchases with saffron store. Explore our catalog to shop our premium products!</p>
            <a 
                href="/shop" 
                class="mt-6 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
            >
                Start Shopping
            </a>
        </div>
    @endif
</div>
