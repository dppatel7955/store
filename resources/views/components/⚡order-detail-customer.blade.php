<?php

use Livewire\Component;
use App\Models\Order;

new class extends Component
{
    public int $id;
    public Order $order;

    public function mount(int $id)
    {
        $this->id = $id;
        $this->order = Order::with(['items.product'])->where('id', $id)->where('user_id', auth()->id())->firstOrFail();
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <!-- Breadcrumbs / Back button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="/orders" class="p-2 bg-white border border-slate-200 rounded-xl text-slate-505 hover:text-slate-900 hover:shadow shadow-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Order Details</h1>
                <p class="text-xs text-slate-550 mt-1">Invoice summary and delivery updates for order #{{ $order->id }}.</p>
            </div>
        </div>
        <div class="sm:text-right">
            <span class="text-xs text-slate-450 font-semibold">Placed on: {{ $order->created_at->format('M d, Y h:i A') }}</span>
        </div>
    </div>

    <!-- Order Tracker -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-450 border-b border-slate-200 pb-3">Shipment Status</h3>
        
        @php
            $status = $order->status;
            $step = match($status) {
                'pending' => 1,
                'processing' => 2,
                'shipped' => 3,
                'delivered' => 4,
                default => 1
            };
            $cancelled = ($status === 'cancelled');
        @endphp

        @if($cancelled)
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700 flex items-center gap-2">
                ⚠️ This order has been cancelled and will not be fulfilled.
            </div>
        @else
            <div class="relative flex items-center justify-between">
                <!-- Line Background -->
                <div class="absolute left-4 right-4 top-1/2 h-0.5 bg-slate-200 -translate-y-1/2 -z-10"></div>
                <!-- Progress Line -->
                <div class="absolute left-4 top-1/2 h-0.5 bg-indigo-600 -translate-y-1/2 -z-10 transition-all duration-500" 
                     style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '33%' : ($step === 3 ? '66%' : '100%')) }};"></div>

                <!-- Steps -->
                <!-- Step 1: Placed -->
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white transition {{ $step >= 1 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500' }}">1</div>
                    <span class="text-xs font-bold mt-2 {{ $step >= 1 ? 'text-slate-800' : 'text-slate-400' }}">Placed</span>
                </div>
                <!-- Step 2: Processing -->
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white transition {{ $step >= 2 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500' }}">2</div>
                    <span class="text-xs font-bold mt-2 {{ $step >= 2 ? 'text-slate-800' : 'text-slate-400' }}">Processing</span>
                </div>
                <!-- Step 3: Shipped -->
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white transition {{ $step >= 3 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500' }}">3</div>
                    <span class="text-xs font-bold mt-2 {{ $step >= 3 ? 'text-slate-800' : 'text-slate-400' }}">Shipped</span>
                </div>
                <!-- Step 4: Delivered -->
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white transition {{ $step >= 4 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500' }}">4</div>
                    <span class="text-xs font-bold mt-2 {{ $step >= 4 ? 'text-slate-800' : 'text-slate-400' }}">Delivered</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Content Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main order details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Line items -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Items Ordered</h3>
                <div class="divide-y divide-slate-200/60">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between py-4">
                            <div class="flex items-center gap-3.5">
                                <div class="h-12 w-12 rounded-lg bg-slate-50 overflow-hidden border border-slate-200 flex-shrink-0">
                                    <img src="{{ is_array($item->product->images) ? ($item->product->images[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop') : 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop' }}" alt="{{ $item->product->name ?? 'Product Image' }}" class="h-full w-full object-cover">
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 leading-snug line-clamp-1">{{ $item->product->name ?? 'Deleted Item' }}</h4>
                                    <span class="text-[10px] text-slate-500 font-medium">SKU: {{ $item->product->sku ?? '-' }} &bull; Qty: {{ $item->quantity }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-900">₹{{ number_format($item->total_amount) }}</span>
                                <span class="block text-[10px] text-slate-450 mt-0.5">₹{{ number_format($item->unit_amount) }} each</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Financial calculation summary -->
                <div class="border-t border-slate-200 pt-4 flex flex-col items-end gap-2 text-xs text-slate-600">
                    <div class="flex justify-between w-64">
                        <span>Items Subtotal:</span>
                        <span class="font-semibold text-slate-800">₹{{ number_format($order->grand_total - $order->shipping_amount) }}</span>
                    </div>
                    <div class="flex justify-between w-64">
                        <span>Shipping Cost:</span>
                        <span class="font-semibold text-slate-800">₹{{ number_format($order->shipping_amount) }}</span>
                    </div>
                    <div class="flex justify-between w-64 border-t border-slate-200 pt-2 text-sm font-black text-slate-900">
                        <span>Grand Total:</span>
                        <span class="text-indigo-650 font-extrabold">₹{{ number_format($order->grand_total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address / Payment information -->
        <div class="space-y-6">
            <!-- Shipping details -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Delivery Address
                </h3>
                
                @php
                    $address = $order->shipping_address;
                @endphp
                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Recipient</span>
                        <span class="block text-slate-850 font-bold mt-0.5">{{ $address['name'] ?? '' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Phone Number</span>
                        <span class="block text-slate-850 font-mono font-bold mt-0.5">{{ $address['phone'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Address</span>
                        <span class="block text-slate-650 leading-relaxed mt-0.5">
                            {{ $address['street'] ?? '' }}<br>
                            {{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }} - {{ $address['zip'] ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment details -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Payment Details
                </h3>
                
                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Payment Method</span>
                        <span class="block text-slate-850 font-bold uppercase mt-0.5">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'Credit Card (Stripe)' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Payment Status</span>
                        <span class="block text-slate-850 font-bold mt-0.5 capitalize">{{ $order->payment_status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
