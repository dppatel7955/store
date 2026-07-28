<?php

use Livewire\Component;
use App\Models\Order;
use App\Services\ShippingService;

new class extends Component
{
    public string $searchQuery = '';
    public ?Order $foundOrder = null;
    public ?string $errorMessage = null;
    public string $estimatedDeliveryDate = '';

    public function mount()
    {
        if (request()->has('order_id')) {
            $this->searchQuery = request('order_id');
            $this->trackOrder();
        }
    }

    public function trackOrder()
    {
        $this->errorMessage = null;
        $this->foundOrder = null;

        $queryStr = trim($this->searchQuery);
        if (empty($queryStr)) {
            $this->errorMessage = 'Please enter an Order ID or Phone Number.';
            return;
        }

        // Clean query string (strip '#' if user typed #000005)
        $cleanId = (int) ltrim($queryStr, '#');

        $order = Order::with(['items.product'])
            ->where(function ($q) use ($cleanId, $queryStr) {
                if ($cleanId > 0) {
                    $q->where('id', $cleanId);
                }
                $q->orWhere('shipping_address->phone', 'like', '%' . $queryStr . '%');
            })
            ->latest()
            ->first();

        if (! $order) {
            $this->errorMessage = 'No order found matching "' . $queryStr . '". Please check your details and try again.';
            return;
        }

        $this->foundOrder = $order;

        // Calculate delivery estimate
        $city = $order->shipping_address['city'] ?? null;
        $state = $order->shipping_address['state'] ?? null;
        $zip = $order->shipping_address['zip'] ?? null;

        $rateData = ShippingService::calculateRate($city, $state, $zip, (float) $order->grand_total);
        $this->estimatedDeliveryDate = $rateData['delivery_date'] ?? '2-4 Business Days';
    }
};
?>

<div class="min-h-screen bg-slate-50/50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Search Header Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-8 sm:p-10 shadow-xs text-center space-y-4 relative overflow-hidden">
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 mb-2">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Track Your Order</h1>
            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                Enter your Order Reference ID or Phone Number to check real-time courier progress and estimated delivery.
            </p>

            <form wire:submit="trackOrder" class="max-w-lg mx-auto flex items-center gap-2 pt-2">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-3 text-slate-400 text-xs">🔍</span>
                    <input 
                        type="text" 
                        wire:model="searchQuery" 
                        placeholder="Order ID (e.g. #000005) or Phone Number"
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3 pl-9 pr-4 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                    />
                </div>
                <button 
                    type="submit" 
                    class="rounded-2xl bg-indigo-600 hover:bg-indigo-500 py-3 px-6 text-xs font-bold text-white shadow-xs transition shrink-0"
                >
                    Track Status
                </button>
            </form>

            @if($errorMessage)
                <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl max-w-lg mx-auto">
                    {{ $errorMessage }}
                </div>
            @endif
        </div>

        <!-- Order Results View -->
        @if($foundOrder)
            <div class="space-y-6">

                <!-- Order Status & Timeline Card -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Order Reference</span>
                            <h2 class="text-xl font-extrabold text-slate-900">#{{ str_pad($foundOrder->id, 6, '0', STR_PAD_LEFT) }}</h2>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Estimated Delivery</span>
                            <span class="text-sm font-extrabold text-indigo-700">{{ $estimatedDeliveryDate }}</span>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ ucfirst($foundOrder->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Tracker -->
                    @php
                        $statusMap = ['new' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4, 'cancelled' => 0];
                        $currentStep = $statusMap[$foundOrder->status] ?? 1;
                        $progressWidth = match($currentStep) {
                            2 => 'w-1/3',
                            3 => 'w-2/3',
                            4 => 'w-full',
                            default => 'w-0'
                        };
                    @endphp
                    <div class="relative py-4 px-2">
                        <div class="absolute left-6 right-6 top-7 h-1 bg-slate-100 -translate-y-1/2 rounded-full"></div>
                        <div class="absolute left-6 {{ $progressWidth }} top-7 h-1 bg-indigo-600 -translate-y-1/2 rounded-full transition-all duration-500"></div>

                        <div class="relative flex justify-between">
                            <div class="flex flex-col items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white {{ $currentStep >= 1 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400' }}">1</div>
                                <span class="text-[11px] font-semibold mt-2 {{ $currentStep >= 1 ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">Placed</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white {{ $currentStep >= 2 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400' }}">2</div>
                                <span class="text-[11px] font-semibold mt-2 {{ $currentStep >= 2 ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">Processing</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white {{ $currentStep >= 3 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400' }}">3</div>
                                <span class="text-[11px] font-semibold mt-2 {{ $currentStep >= 3 ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">Shipped</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-white {{ $currentStep >= 4 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400' }}">4</div>
                                <span class="text-[11px] font-semibold mt-2 {{ $currentStep >= 4 ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">Delivered</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items & Shipping Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    <!-- Items Card -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Items in Shipment</h3>
                        <div class="divide-y divide-slate-100">
                            @foreach($foundOrder->items as $item)
                                <div class="py-2.5 flex items-center justify-between gap-3 first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-10 w-10 bg-slate-50 border border-slate-200 rounded-xl overflow-hidden shrink-0 flex items-center justify-center">
                                            @if(is_array($item->product?->images) && count($item->product->images) > 0)
                                                <img src="{{ $item->product->images[0] }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs">🛍</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-slate-900 truncate">{{ $item->product?->name }}</h4>
                                            <span class="text-[11px] text-slate-500">Qty: {{ $item->quantity }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-900 shrink-0">₹{{ number_format($item->total_amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Destination Card -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-3">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Destination Address</h3>
                        <div class="text-xs text-slate-700 leading-relaxed space-y-1">
                            <p class="font-bold text-slate-900 text-sm">{{ $foundOrder->shipping_address['name'] ?? '' }}</p>
                            <p>{{ $foundOrder->shipping_address['street'] ?? '' }}</p>
                            <p>{{ $foundOrder->shipping_address['city'] ?? '' }}, {{ $foundOrder->shipping_address['state'] ?? '' }} - {{ $foundOrder->shipping_address['zip'] ?? '' }}</p>
                            <p class="pt-1 text-slate-500 font-medium">📞 {{ $foundOrder->shipping_address['phone'] ?? '' }}</p>
                        </div>
                    </div>

                </div>

            </div>
        @endif

    </div>
</div>
