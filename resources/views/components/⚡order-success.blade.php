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

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-10">
    <!-- Success Banner -->
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center h-20 w-20 rounded-full bg-emerald-50 border-4 border-emerald-100 text-emerald-600 mb-2 relative">
            <div class="absolute inset-0 rounded-full bg-emerald-500/10 animate-ping"></div>
            <svg class="h-10 w-10 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">Payment Successful!</h1>
        <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto leading-relaxed">
            Thank you for your purchase! Your payment has been processed securely, and your order is officially confirmed.
        </p>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Column: Timeline & Info -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Order Timeline Tracker -->
            @php
                $statusMap = [
                    'new' => 1,
                    'processing' => 2,
                    'shipped' => 3,
                    'delivered' => 4,
                    'cancelled' => 0
                ];
                $currentStep = $statusMap[$order->status] ?? 1;
                $progressWidth = match($currentStep) {
                    2 => 'w-1/3',
                    3 => 'w-2/3',
                    4 => 'w-full',
                    default => 'w-0'
                };
            @endphp
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">
                    Delivery Progress Tracker
                </h3>
                
                <div class="relative flex items-center justify-between px-2 pt-2">
                    <!-- Line Background -->
                    <div class="absolute left-6 right-6 top-[18px] h-1 bg-slate-100 -translate-y-1/2 -z-10 rounded-full"></div>
                    <!-- Progress Line -->
                    <div class="absolute left-6 {{ $progressWidth }} top-[18px] h-1 bg-indigo-650 -translate-y-1/2 -z-10 rounded-full transition-all duration-700 ease-out"></div>

                    <!-- Step 1: Placed -->
                    <div class="flex flex-col items-center">
                        <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ring-4 ring-white {{ $currentStep >= 1 ? 'bg-indigo-650 text-white shadow-md' : 'bg-slate-100 text-slate-400' }}">
                            1
                        </div>
                        <span class="text-[10px] sm:text-xs font-bold mt-2 {{ $currentStep >= 1 ? 'text-indigo-600' : 'text-slate-400' }}">Placed</span>
                    </div>

                    <!-- Step 2: Processing -->
                    <div class="flex flex-col items-center">
                        <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ring-4 ring-white {{ $currentStep >= 2 ? 'bg-indigo-650 text-white shadow-md' : 'bg-slate-100 text-slate-400' }}">
                            2
                        </div>
                        <span class="text-[10px] sm:text-xs font-bold mt-2 {{ $currentStep >= 2 ? 'text-indigo-600' : 'text-slate-400' }}">Processing</span>
                    </div>

                    <!-- Step 3: Shipped -->
                    <div class="flex flex-col items-center">
                        <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ring-4 ring-white {{ $currentStep >= 3 ? 'bg-indigo-650 text-white shadow-md' : 'bg-slate-100 text-slate-400' }}">
                            3
                        </div>
                        <span class="text-[10px] sm:text-xs font-bold mt-2 {{ $currentStep >= 3 ? 'text-indigo-600' : 'text-slate-400' }}">Shipped</span>
                    </div>

                    <!-- Step 4: Delivered -->
                    <div class="flex flex-col items-center">
                        <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ring-4 ring-white {{ $currentStep >= 4 ? 'bg-indigo-650 text-white shadow-md' : 'bg-slate-100 text-slate-400' }}">
                            4
                        </div>
                        <span class="text-[10px] sm:text-xs font-bold mt-2 {{ $currentStep >= 4 ? 'text-indigo-600' : 'text-slate-400' }}">Delivered</span>
                    </div>
                </div>
            </div>

            <!-- Delivery & Payment Information -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <!-- Delivery Info -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Delivery Information</h4>
                        <div class="text-xs sm:text-sm text-slate-800 space-y-1">
                            <p class="font-bold">{{ $order->shipping_address['name'] }}</p>
                            <p class="text-slate-500 leading-relaxed">
                                {{ $order->shipping_address['street'] }},<br>
                                {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['state'] }} - {{ $order->shipping_address['zip'] }}
                            </p>
                            <p class="text-slate-500 pt-1">Phone: {{ $order->shipping_address['phone'] }}</p>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Transaction Details</h4>
                        <div class="text-xs sm:text-sm text-slate-800 space-y-3">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Order ID</span>
                                <span class="font-mono font-bold text-slate-700">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Payment Mode</span>
                                <span class="font-bold text-indigo-750 uppercase">{{ $order->payment_method }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Payment Status</span>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                    {{ $order->payment_status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Digital Receipt Summary -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">
                Digital Receipt
            </h3>

            <!-- Products List -->
            <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto pr-1">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between py-3 gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 flex-shrink-0 rounded-xl bg-slate-50 overflow-hidden border border-slate-200/60 p-0.5">
                                @if(is_array($item->product->images) && count($item->product->images) > 0)
                                    <img src="{{ $item->product->images[0] }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover rounded-lg">
                                @else
                                    <div class="h-full w-full bg-slate-100 flex items-center justify-center text-slate-400">🛍</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-800 truncate">{{ $item->product->name }}</h4>
                                <span class="text-[10px] sm:text-xs text-slate-400 font-medium">₹{{ number_format($item->unit_amount) }} &times; {{ $item->quantity }}</span>
                            </div>
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900 shrink-0">₹{{ number_format($item->total_amount) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="border-t border-slate-150 pt-4 space-y-2.5 text-xs sm:text-sm text-slate-500">
                <div class="flex justify-between">
                    <span>Shipping charge</span>
                    <span class="font-bold text-slate-800">
                        @if($order->shipping_amount > 0)
                            ₹{{ number_format($order->shipping_amount) }}
                        @else
                            <span class="text-emerald-600 font-black uppercase text-[10px]">Free</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between text-base font-black text-slate-900 pt-3 border-t border-slate-100">
                    <span>Total Paid</span>
                    <span class="text-indigo-650 text-lg">₹{{ number_format($order->grand_total) }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Actions -->
    <div class="text-center pt-4">
        <a href="/shop" class="inline-flex items-center justify-center rounded-2xl bg-indigo-650 hover:bg-indigo-700 py-3 px-8 text-xs font-bold text-white shadow hover:shadow-lg transition duration-300 gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Continue Shopping
        </a>
    </div>
</div>