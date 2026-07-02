<?php

use Livewire\Component;
use App\Models\Order;

new class extends Component
{
    public Order $order;
    public string $status = '';
    public string $payment_status = '';

    public function mount(int $id)
    {
        $this->order = Order::with(['user', 'items.product'])->findOrFail($id);
        $this->status = $this->order->status;
        $this->payment_status = $this->order->payment_status;
    }

    public function updateOrderStatus()
    {
        $this->order->status = $this->status;
        $this->order->save();
        session()->flash('success', 'Order status updated successfully.');
    }

    public function updatePaymentStatus()
    {
        $this->order->payment_status = $this->payment_status;
        $this->order->save();
        session()->flash('success', 'Payment status updated successfully.');
    }

    public function sendInvoiceEmail()
    {
        try {
            $recipientEmail = $this->order->shipping_address['email'] ?? $this->order->user->email;
            \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\OrderInvoiceMail($this->order));
            $this->dispatch('swal', title: 'Success!', text: 'Invoice email sent successfully to ' . $recipientEmail, icon: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send manually: ' . $e->getMessage());
            $this->dispatch('swal', title: 'Error!', text: 'Failed to send email. Check SMTP settings. Error: ' . $e->getMessage(), icon: 'error');
        }
    }
};
?>

<div class="space-y-6">
    <!-- Breadcrumbs / Back button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders') }}" class="p-2 bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-slate-900 hover:shadow shadow-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Order Details</h1>
                <p class="text-xs text-slate-500 mt-1">Invoice summary and shipping status for order #{{ $order->id }}.</p>
            </div>
        </div>
        <div class="sm:text-right">
            <span class="text-xs text-slate-450 font-semibold">Placed on: {{ $order->created_at->format('M d, Y h:i A') }}</span>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Details Card -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items Table -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Line Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                                <th class="pb-3">Product</th>
                                <th class="pb-3">SKU</th>
                                <th class="pb-3 text-right">Price</th>
                                <th class="pb-3 text-center">Qty</th>
                                <th class="pb-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 text-xs">
                            @foreach($order->items as $item)
                                <tr>
                                    <!-- Image & Name -->
                                    <td class="py-3">
                                        <div class="flex items-center gap-3">
                                            @if(!empty($item->variant?->images) && is_array($item->variant->images))
                                                <img src="{{ $item->variant->images[0] }}" class="h-8 w-8 object-cover rounded border border-slate-200 flex-shrink-0">
                                            @elseif($item->product && is_array($item->product->images))
                                                <img src="{{ $item->product->images[0] ?? '' }}" class="h-8 w-8 object-cover rounded border border-slate-200 flex-shrink-0">
                                            @endif
                                            <div>
                                                <div class="font-bold text-slate-800">{{ $item->product->name ?? 'Deleted Product' }}</div>
                                                @if(!empty($item->variant_name))
                                                    <div class="text-[10px] text-indigo-600 font-medium">Variant: {{ $item->variant_name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <!-- SKU -->
                                    <td class="py-3 text-slate-450 font-mono">{{ $item->product->sku ?? '-' }}</td>
                                    <!-- Price -->
                                    <td class="py-3 text-right text-slate-600 font-medium">₹{{ number_format($item->unit_amount) }}</td>
                                    <!-- Qty -->
                                    <td class="py-3 text-center text-slate-700 font-semibold">{{ $item->quantity }}</td>
                                    <!-- Total -->
                                    <td class="py-3 text-right font-bold text-slate-900">₹{{ number_format($item->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Summary Breakdown -->
                <div class="border-t border-slate-200 pt-4 flex flex-col items-end gap-2 text-xs">
                    <div class="flex justify-between w-64 text-slate-500">
                        <span>Items Subtotal:</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($order->grand_total - $order->shipping_amount) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-500">
                        <span>Shipping Cost:</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($order->shipping_amount) }}</span>
                    </div>
                    <div class="flex justify-between w-64 border-t border-slate-200 pt-2 text-sm font-black text-slate-900">
                        <span>Grand Total:</span>
                        <span class="text-indigo-650 font-extrabold">₹{{ number_format($order->grand_total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Shipping address card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Fulfillment & Shipping Address
                </h3>
                
                @php
                    $address = $order->shipping_address;
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="space-y-1">
                        <span class="block text-slate-500 font-semibold">Recipient Name</span>
                        <span class="block text-slate-800 font-bold">{{ $address['name'] ?? '' }}</span>
                    </div>
                    <div class="space-y-1">
                        <span class="block text-slate-500 font-semibold">Phone Number</span>
                        <span class="block text-slate-800 font-mono">{{ $address['phone'] ?? '-' }}</span>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <span class="block text-slate-500 font-semibold">Street Address</span>
                        <span class="block text-slate-800 leading-relaxed">{{ $address['street'] ?? '' }}</span>
                    </div>
                    <div class="space-y-1">
                        <span class="block text-slate-500 font-semibold">City & State</span>
                        <span class="block text-slate-800">{{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }}</span>
                    </div>
                    <div class="space-y-1">
                        <span class="block text-slate-500 font-semibold">Postal Code / ZIP</span>
                        <span class="block text-slate-800 font-mono">{{ $address['zip'] ?? '' }}</span>
                    </div>
                    @if($order->notes)
                        <div class="space-y-1 sm:col-span-2 border-t border-slate-200 pt-3">
                            <span class="block text-slate-500 font-semibold">Customer Notes</span>
                            <p class="block text-slate-600 italic leading-relaxed">"{{ $order->notes }}"</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Status Panel -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Management Panel</h3>

                <!-- Order Status -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-500">Order Status</label>
                    <select 
                        wire:model="status" 
                        wire:change="updateOrderStatus"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                    >
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div class="space-y-2 border-t border-slate-200 pt-4">
                    <label class="block text-xs font-semibold text-slate-500">Payment Status</label>
                    <select 
                        wire:model="payment_status" 
                        wire:change="updatePaymentStatus"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                    >
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <!-- Quick actions -->
                <div class="border-t border-slate-200 pt-4 text-xs text-slate-500 space-y-2">
                    <div class="flex justify-between">
                        <span>Payment Method:</span>
                        <span class="font-bold text-slate-700 uppercase">{{ $order->payment_method }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipment Method:</span>
                        <span class="font-bold text-slate-700">{{ $order->shipping_method ?? 'Flat Rate' }}</span>
                    </div>
                </div>

                <!-- Management Actions -->
                <div class="border-t border-slate-200 pt-4 space-y-2.5">
                    <button 
                        type="button" 
                        wire:click="sendInvoiceEmail"
                        class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2.5 text-xs font-bold text-white shadow-sm transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5a2 2 0 00-2.22 0l-2.25 1.5" />
                        </svg>
                        Send Invoice Email
                    </button>

                    <a 
                        href="{{ route('admin.orders.invoice', ['id' => $order->id]) }}" 
                        target="_blank"
                        class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 border border-slate-200 hover:bg-slate-200 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4" />
                        </svg>
                        Print Invoice
                    </a>
                </div>
            </div>

            <!-- Customer mini Profile -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Customer Info</h3>
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-sm">
                        {{ substr($order->user->name, 0, 1) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-800 leading-normal">{{ $order->user->name }}</span>
                        <span class="text-[10px] text-slate-450">{{ $order->user->email }}</span>
                    </div>
                </div>
                <div class="border-t border-slate-200 pt-3 text-[10px] text-slate-500 font-semibold">
                    Joined Date: {{ $order->user->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>
</div>
