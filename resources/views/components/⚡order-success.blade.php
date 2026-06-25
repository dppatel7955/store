<?php

use Livewire\Component;
use App\Models\Order;

new class extends Component
{
    public Order $order;

    public function mount(int $id)
    {
        $this->order = Order::with(['items.product'])->where('user_id', auth()->id())->findOrFail($id);
    }
};
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center space-y-12">
    <!-- Success Banner -->
    <div class="space-y-4">
        <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 mb-4 animate-bounce">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900">Order Confirmed!</h1>
        <p class="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
            Thank you for your purchase. Your order has been placed successfully and is being processed by our warehouse.
        </p>
    </div>

    <!-- Order Timeline Tracker -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 text-left shadow-sm">
        <h3 class="text-sm font-bold text-slate-805 uppercase tracking-wider border-b border-slate-200 pb-3">Shipment Status</h3>
        
        <div class="relative flex items-center justify-between">
            <!-- Line Background -->
            <div class="absolute left-4 right-4 top-1/2 h-0.5 bg-slate-200 -translate-y-1/2 -z-10"></div>
            <!-- Progress Line -->
            <div class="absolute left-4 w-1/4 top-1/2 h-0.5 bg-indigo-500 -translate-y-1/2 -z-10"></div>

            <!-- Steps -->
            <div class="flex flex-col items-center">
                <div class="h-8 w-8 rounded-full bg-indigo-650 text-white flex items-center justify-center text-xs font-bold ring-4 ring-white">1</div>
                <span class="text-xs font-bold text-slate-800 mt-2">Placed</span>
            </div>
            <div class="flex flex-col items-center">
                <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold ring-4 ring-white">2</div>
                <span class="text-xs font-medium text-slate-500 mt-2">Processing</span>
            </div>
            <div class="flex flex-col items-center">
                <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold ring-4 ring-white">3</div>
                <span class="text-xs font-medium text-slate-500 mt-2">Shipped</span>
            </div>
            <div class="flex flex-col items-center">
                <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold ring-4 ring-white">4</div>
                <span class="text-xs font-medium text-slate-500 mt-2">Delivered</span>
            </div>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 text-left shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between border-b border-slate-200 pb-4 gap-2">
            <div>
                <span class="text-xs text-slate-500 font-semibold">Order ID</span>
                <h4 class="text-sm font-bold text-slate-800">#{{ $order->id }}</h4>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-semibold">Payment Method</span>
                <h4 class="text-sm font-bold text-slate-800 uppercase">{{ $order->payment_method }}</h4>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-semibold">Payment Status</span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                    {{ $order->payment_status }}
                </span>
            </div>
        </div>

        <!-- Products -->
        <div class="space-y-4">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Items Ordered</h4>
            <div class="divide-y divide-slate-200">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 flex-shrink-0 rounded bg-slate-50 overflow-hidden border border-slate-200">
                                <img src="{{ $item->product->images[0] }}" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">{{ $item->product->name }}</h4>
                                <span class="text-[10px] text-slate-500">₹{{ number_format($item->unit_amount) }} x {{ $item->quantity }}</span>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-slate-900">₹{{ number_format($item->total_amount) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Shipping Destination -->
        <div class="border-t border-slate-200 pt-6">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3">Delivery Information</h4>
            <p class="text-xs text-slate-800 font-semibold">{{ $order->shipping_address['name'] }}</p>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                {{ $order->shipping_address['street'] }}, {{ $order->shipping_address['city'] }}, <br>
                {{ $order->shipping_address['state'] }} - {{ $order->shipping_address['zip'] }}
            </p>
            <p class="text-xs text-slate-500 mt-1.5">Phone: {{ $order->shipping_address['phone'] }}</p>
        </div>

        <!-- Order Total -->
        <div class="border-t border-slate-200 pt-4 space-y-2 text-sm text-slate-500">
            <div class="flex justify-between">
                <span>Shipping amount</span>
                <span class="font-medium text-slate-800">₹{{ number_format($order->shipping_amount) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                <span>Amount Paid</span>
                <span class="text-indigo-650">₹{{ number_format($order->grand_total) }}</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div>
        <a href="/shop" class="inline-flex items-center justify-center rounded-xl bg-slate-100 border border-slate-200 hover:bg-slate-200 hover:text-slate-900 py-3 px-6 text-sm font-bold text-slate-705 shadow-sm transition duration-300">
            Continue Shopping Catalog
        </a>
    </div>
</div>