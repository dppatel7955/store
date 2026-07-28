<?php

use Livewire\Component;
use App\Models\Order;
use App\Services\ShippingService;

new class extends Component
{
    public Order $order;
    public string $estimatedDeliveryDate = '';

    public function mount(int $id)
    {
        $query = Order::with(['items.product', 'paymentMethodConfig']);
        if (! auth()->user()->is_admin) {
            $query->where('user_id', auth()->id());
        }
        $this->order = $query->findOrFail($id);

        // Calculate estimated delivery date based on shipping address
        $city = $this->order->shipping_address['city'] ?? null;
        $state = $this->order->shipping_address['state'] ?? null;
        $zip = $this->order->shipping_address['zip'] ?? null;

        $rateData = ShippingService::calculateRate($city, $state, $zip, (float) $this->order->grand_total);
        $this->estimatedDeliveryDate = $rateData['delivery_date'] ?? '2-4 Business Days';
    }
};
?>

<div class="min-h-screen bg-slate-50/50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Hero Success Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-8 sm:p-10 shadow-xs text-center relative overflow-hidden">
            <!-- Subtle Top Accent Gradient -->
            <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-600"></div>

            <div class="inline-flex items-center justify-center h-20 w-20 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 mb-5 relative shadow-xs">
                <div class="absolute inset-0 rounded-2xl bg-emerald-500/10 animate-ping"></div>
                <svg class="h-10 w-10 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Order Confirmed!</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-lg mx-auto leading-relaxed">
                Thank you for your purchase. We have received your order and sent a confirmation details email to 
                <span class="font-semibold text-slate-900">{{ auth()->user()->email }}</span>.
            </p>

            <div class="mt-6 inline-flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs" x-data="{ copied: false }">
                <span class="text-slate-500 font-medium">Order Reference:</span>
                <span class="font-mono font-bold text-slate-900">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                <button 
                    @click="navigator.clipboard.writeText('#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="text-indigo-600 hover:text-indigo-700 font-bold transition ml-1"
                >
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
        </div>

        <!-- Delivery Status & Progress Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Expected Delivery</h2>
                    <p class="text-lg font-extrabold text-indigo-700 mt-0.5">{{ $estimatedDeliveryDate }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Current Status:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>

            <!-- Progress Tracker -->
            @php
                $statusMap = ['new' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4, 'cancelled' => 0];
                $currentStep = $statusMap[$order->status] ?? 1;
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

        <!-- 2-Column Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Items & Shipping Address (2 cols) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Items Purchased -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Items Ordered</h3>

                    <div class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                            <div class="py-3.5 flex items-center gap-4 first:pt-0 last:pb-0">
                                <div class="h-16 w-16 flex-shrink-0 bg-slate-50 border border-slate-200/80 rounded-2xl overflow-hidden p-1">
                                    @if(is_array($item->product?->images) && count($item->product->images) > 0)
                                        <img src="{{ $item->product->images[0] }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover rounded-xl">
                                    @else
                                        <div class="h-full w-full flex items-center justify-center text-slate-400 text-lg">🛍</div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $item->product?->name ?? 'Product' }}</h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Quantity: <span class="font-semibold text-slate-800">{{ $item->quantity }}</span></p>
                                    <p class="text-[11px] text-slate-500">Unit Price: ₹{{ number_format($item->unit_amount, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs sm:text-sm font-extrabold text-slate-900">₹{{ number_format($item->total_amount, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Delivery Address Card -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Delivery Address
                    </h3>
                    <div class="text-xs text-slate-700 leading-relaxed space-y-1">
                        <p class="font-bold text-slate-900 text-sm">{{ $order->shipping_address['name'] ?? auth()->user()->name }}</p>
                        <p>{{ $order->shipping_address['street'] ?? '' }}</p>
                        <p>{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} - {{ $order->shipping_address['zip'] ?? '' }}</p>
                        <p class="pt-1 text-slate-500 font-medium">📞 {{ $order->shipping_address['phone'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary & Actions (1 col) -->
            <div class="space-y-6">

                <!-- Receipt Totals Card -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">Payment Summary</h3>

                    <div class="space-y-2.5 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold text-slate-900">₹{{ number_format((float) $order->grand_total - (float) $order->shipping_amount + (float) $order->discount_amount, 2) }}</span>
                        </div>

                        @if($order->discount_amount > 0)
                            <div class="flex justify-between text-emerald-700 font-semibold">
                                <span>Discount ({{ $order->coupon_code }})</span>
                                <span>-₹{{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <span>Shipping Fee</span>
                            @if($order->shipping_amount > 0)
                                <span class="font-semibold text-slate-900">₹{{ number_format($order->shipping_amount, 2) }}</span>
                            @else
                                <span class="text-emerald-700 font-extrabold bg-emerald-50 px-2 py-0.5 rounded uppercase text-[10px]">FREE Delivery</span>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-sm font-extrabold text-slate-900">
                            <span>Total Paid</span>
                            <span class="text-indigo-600 text-base">₹{{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Payment Method:</span>
                            <span class="font-bold text-slate-800">{{ $order->paymentMethodConfig?->name ?? strtoupper($order->payment_method) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Payment Status:</span>
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded-full {{ $order->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $order->payment_status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="space-y-3">
                    <a 
                        href="/orders" 
                        class="w-full rounded-2xl bg-indigo-600 hover:bg-indigo-500 py-3 px-4 text-xs font-bold text-white shadow-xs transition flex items-center justify-center gap-2"
                    >
                        View Order History
                    </a>
                    <a 
                        href="/shop" 
                        class="w-full rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 py-3 px-4 text-xs font-bold text-slate-700 transition flex items-center justify-center gap-2"
                    >
                        Continue Shopping
                    </a>
                </div>

            </div>

        </div>

        <!-- Footer Guarantees -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center pt-4">
            <div class="bg-white border border-slate-200/60 rounded-2xl p-4 text-xs text-slate-600">
                <span class="block font-bold text-slate-900 mb-1">🔒 256-Bit SSL Encryption</span>
                Your payment and personal data are 100% secure.
            </div>
            <div class="bg-white border border-slate-200/60 rounded-2xl p-4 text-xs text-slate-600">
                <span class="block font-bold text-slate-900 mb-1">🚚 Express Regional Courier</span>
                Tracked shipment with doorstep delivery.
            </div>
            <div class="bg-white border border-slate-200/60 rounded-2xl p-4 text-xs text-slate-600">
                <span class="block font-bold text-slate-900 mb-1">💬 Dedicated Support</span>
                Need assistance? Contact our team anytime.
            </div>
        </div>

    </div>
</div>