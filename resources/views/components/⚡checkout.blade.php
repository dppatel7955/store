<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;

new class extends Component
{
    public array $cart = [];
    public float $subtotal = 0;
    public float $shipping = 0;
    public float $grandTotal = 0;

    // Shipping info
    public string $name = '';
    public string $email = '';
    public string $street = '';
    public string $city = '';
    public string $state = '';
    public string $zip = '';
    public string $phone = '';
    public string $paymentMethod = 'cod';
    public string $notes = '';

    public function mount()
    {
        $this->cart = CartService::get();
        if (count($this->cart) === 0) {
            return redirect('/shop');
        }
        $this->subtotal = CartService::getSubtotal();
        if ($this->subtotal > 50000) {
            $this->shipping = 0.00;
        }
        $this->grandTotal = $this->subtotal + $this->shipping;

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }
    }

    public function placeOrder()
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $this->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'street' => 'required|string|min:5',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'paymentMethod' => 'required|in:cod,stripe',
        ]);

        $order = Order::create([
            'user_id' => auth()->id(),
            'grand_total' => $this->grandTotal,
            'payment_method' => $this->paymentMethod,
            'payment_status' => $this->paymentMethod === 'cod' ? 'pending' : 'paid',
            'status' => 'pending',
            'shipping_amount' => $this->shipping,
            'shipping_method' => $this->shipping > 0 ? 'Standard Delivery' : 'Free Express Delivery',
            'shipping_address' => [
                'name' => $this->name,
                'email' => $this->email,
                'street' => $this->street,
                'city' => $this->city,
                'state' => $this->state,
                'zip' => $this->zip,
                'phone' => $this->phone,
            ],
            'notes' => $this->notes,
        ]);

        foreach ($this->cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'unit_amount' => $item['price'],
                'total_amount' => $item['total'],
            ]);

            $product = Product::find($item['id']);
            if ($product) {
                $product->stock = max(0, $product->stock - $item['quantity']);
                $product->save();
            }
        }

        CartService::clear();
        $this->dispatch('cart-updated');

        // Send Invoice Email to Customer
        try {
            $recipientEmail = $order->shipping_address['email'] ?? auth()->user()->email;
            \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\OrderInvoiceMail($order));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout email failed: ' . $e->getMessage());
        }

        return redirect()->to('/order-success/' . $order->id);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold text-slate-900 mb-8">Checkout</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Details Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900 border-b border-slate-200 pb-3">Shipping Address</h2>
                
                @if(!auth()->check())
                    <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-xs text-indigo-700">
                        You must be <a href="{{ route('login', ['redirect' => 'checkout']) }}" class="font-bold underline">signed in</a> to complete your purchase order.
                    </div>
                @endif

                <form wire:submit="placeOrder" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-semibold text-slate-500 mb-1.5">Full Name</label>
                        <input id="name" type="text" wire:model="name" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-500 mb-1.5">Email Address</label>
                        <input id="email" type="email" wire:model="email" class="w-full bg-slate-100 border border-slate-250 rounded-xl py-2 px-3 text-sm text-slate-500" disabled>
                        @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-500 mb-1.5">Phone Number</label>
                        <input id="phone" type="text" wire:model="phone" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('phone') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="street" class="block text-xs font-semibold text-slate-500 mb-1.5">Street Address</label>
                        <input id="street" type="text" wire:model="street" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('street') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="city" class="block text-xs font-semibold text-slate-500 mb-1.5">City</label>
                        <input id="city" type="text" wire:model="city" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('city') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="state" class="block text-xs font-semibold text-slate-500 mb-1.5">State</label>
                        <input id="state" type="text" wire:model="state" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('state') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="zip" class="block text-xs font-semibold text-slate-500 mb-1.5">Zip / Postal Code</label>
                        <input id="zip" type="text" wire:model="zip" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('zip') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-xs font-semibold text-slate-500 mb-1.5">Order Notes (Optional)</label>
                        <textarea id="notes" wire:model="notes" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}></textarea>
                    </div>

                    <!-- Payment Options -->
                    <div class="sm:col-span-2 border-t border-slate-200 pt-6 mt-4 space-y-4">
                        <h3 class="text-sm font-bold text-slate-900">Payment Method</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center justify-between border border-slate-200 bg-slate-50 p-4 rounded-xl cursor-pointer hover:border-indigo-600 hover:shadow-sm transition select-none">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model="paymentMethod" value="cod" class="text-indigo-600 focus:ring-indigo-600">
                                    <span class="text-sm font-semibold text-slate-850">Cash on Delivery</span>
                                </span>
                            </label>
                            
                            <label class="flex items-center justify-between border border-slate-200 bg-slate-50 p-4 rounded-xl cursor-pointer hover:border-indigo-600 hover:shadow-sm transition select-none">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model="paymentMethod" value="stripe" class="text-indigo-600 focus:ring-indigo-600">
                                    <span class="text-sm font-semibold text-slate-850">Mock Credit Card (Stripe)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-2 pt-6">
                        <button 
                            type="submit" 
                            class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3.5 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2"
                            {{ !auth()->check() ? 'disabled' : '' }}
                        >
                            Place Order (₹{{ number_format($grandTotal) }})
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sticky top-24 space-y-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-200 pb-3">Order Summary</h2>
                
                <div class="divide-y divide-slate-200 max-h-60 overflow-y-auto pr-2">
                    @foreach($cart as $item)
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex-shrink-0 rounded bg-slate-50 overflow-hidden border border-slate-200">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800 line-clamp-1">{{ $item['name'] }}</h4>
                                    <span class="text-[10px] text-slate-500">Qty: {{ $item['quantity'] }}</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-900">₹{{ number_format($item['total']) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-200 pt-4 space-y-2.5 text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-800">₹{{ number_format($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        @if($shipping > 0)
                            <span class="font-semibold text-slate-800">₹{{ number_format($shipping) }}</span>
                        @else
                            <span class="font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md font-bold text-xs ring-1 ring-inset ring-emerald-600/10 uppercase">Free</span>
                        @endif
                    </div>
                    <div class="flex justify-between text-base font-bold text-slate-800 border-t border-slate-200 pt-3 mt-1">
                        <span>Grand Total</span>
                        <span class="text-indigo-650 font-extrabold">₹{{ number_format($grandTotal) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>