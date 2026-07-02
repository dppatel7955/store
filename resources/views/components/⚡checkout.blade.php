<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\On;

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
            return redirect()->route('shop');
        }
        $this->subtotal = CartService::getSubtotal();
        if ($this->subtotal > 50000) {
            $this->shipping = 0.00;
        }
        $this->grandTotal = $this->subtotal + $this->shipping;

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
            $this->phone = auth()->user()->phone ?? '';
        }
    }

    public function placeOrder()
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        // Enforce email verification before placing an order
        if ($user->email_verified_at === null) {
            // Generate verification code
            $code = (string) rand(100000, 999999);

            // Store code in database
            \App\Models\VerificationCode::updateOrCreate(
                ['type' => 'email_verify', 'identifier' => $user->email],
                [
                    'code' => $code,
                    'expires_at' => now()->addMinutes(10),
                    'verified_at' => null,
                ]
            );

            // Dispatch Email
            \Illuminate\Support\Facades\Log::info("Verification code for unverified checkout ({$user->email}): {$code}");
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerificationMail($code));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send checkout verification email: " . $e->getMessage());
            }

            // Redirect to verify-email portal with return path back to checkout page
            return redirect()->route('verify-email', [
                'email' => $user->email,
                'redirect' => route('checkout', [], false)
            ]);
        }

        $this->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'street' => 'required|string|min:5',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|numeric|digits:10',
            'paymentMethod' => 'required|in:cod,razorpay',
        ]);

        $order = Order::create([
            'user_id' => auth()->id(),
            'grand_total' => $this->grandTotal,
            'payment_method' => $this->paymentMethod,
            'payment_status' => 'pending',
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
                'variant_id' => $item['variant_id'] ?? null,
                'variant_name' => $item['variant_name'] ?? null,
                'quantity' => $item['quantity'],
                'unit_amount' => $item['price'],
                'total_amount' => $item['total'],
            ]);

            if (!empty($item['variant_id'])) {
                $variant = \App\Models\ProductVariant::find($item['variant_id']);
                if ($variant) {
                    $variant->stock = max(0, $variant->stock - $item['quantity']);
                    $variant->save();
                }
            } else {
                $product = Product::find($item['id']);
                if ($product) {
                    $product->stock = max(0, $product->stock - $item['quantity']);
                    $product->save();
                }
            }
        }

        // If Razorpay, initiate online payment checkout
        if ($this->paymentMethod === 'razorpay') {
            $keyId = config('services.razorpay.key_id');
            $keySecret = config('services.razorpay.key_secret');

            if (empty($keyId) || empty($keySecret)) {
                \Illuminate\Support\Facades\Log::error("Razorpay API Keys are not configured in services.php or .env.");
                $this->dispatch('swal', 
                    title: 'Gateway Config Error', 
                    text: 'Razorpay payment key is not configured in .env file. Please contact support.', 
                    icon: 'error'
                );
                return;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::withBasicAuth($keyId, $keySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => round($this->grandTotal * 100), // amount in paisa
                        'currency' => 'INR',
                        'receipt' => 'order_rcptid_' . $order->id,
                    ]);

                if ($response->failed()) {
                    \Illuminate\Support\Facades\Log::error("Razorpay order API failed: " . $response->body());
                    $this->dispatch('swal', 
                        title: 'Payment Setup Failed', 
                        text: 'Failed to initialize payment gateway. Please try again or choose Cash on Delivery.', 
                        icon: 'error'
                    );
                    return;
                }

                $razorpayOrderId = $response->json('id');
                
                // Store Razorpay Order ID securely in session for signature verification
                session(['razorpay_order_id_' . $order->id => $razorpayOrderId]);

                // Dispatch event to launch client-side popup iframe
                $this->dispatch('open-razorpay-checkout', [
                    'key' => $keyId,
                    'amount' => round($this->grandTotal * 100),
                    'orderId' => $razorpayOrderId,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'appOrderDbId' => $order->id
                ]);

                return;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Razorpay Connection Error: " . $e->getMessage());
                $this->dispatch('swal', 
                    title: 'Connection Error', 
                    text: 'Unable to reach payment gateway. Please check your internet connection.', 
                    icon: 'error'
                );
                return;
            }
        }

        // COD Flow: complete immediately
        CartService::clear();
        $this->dispatch('cart-updated');

        // Send Invoice Email to Customer
        try {
            $recipientEmail = $order->shipping_address['email'] ?? auth()->user()->email;
            \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\OrderInvoiceMail($order));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout email failed: ' . $e->getMessage());
        }

        return redirect()->route('order-success', ['id' => $order->id]);
    }

    /**
     * Handle successful Razorpay client validation callback.
     */
    #[On('razorpay-payment-success')]
    public function handleRazorpaySuccess($paymentId, $signature, $dbOrderId)
    {
        $order = Order::findOrFail($dbOrderId);
        $keySecret = config('services.razorpay.key_secret');
        
        $razorpayOrderId = session('razorpay_order_id_' . $dbOrderId);

        if (empty($razorpayOrderId)) {
            $this->dispatch('swal', 
                title: 'Session Expired', 
                text: 'Payment session expired. Please verify your order status with customer service.', 
                icon: 'error'
            );
            return;
        }

        // Verify SHA256 HMAC Signature
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $keySecret);

        if ($expectedSignature !== $signature) {
            \Illuminate\Support\Facades\Log::error("Razorpay Signature mismatch! Order: {$dbOrderId}");
            $this->dispatch('swal', 
                title: 'Payment Verification Failed', 
                text: 'Transaction signature mismatch. Please contact support.', 
                icon: 'error'
            );
            return;
        }

        // Update Order record
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        // Clean session and cart
        session()->forget('razorpay_order_id_' . $dbOrderId);
        CartService::clear();
        $this->dispatch('cart-updated');

        // Send Invoice Email to Customer
        try {
            $recipientEmail = $order->shipping_address['email'] ?? auth()->user()->email;
            \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\OrderInvoiceMail($order));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout email failed: ' . $e->getMessage());
        }

        return redirect()->route('order-success', ['id' => $order->id]);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Global Loading Overlay for Payment Verification -->
    <div wire:loading wire:target="handleRazorpaySuccess" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
        <div class="bg-white p-8 rounded-2xl shadow-xl flex flex-col items-center space-y-4 max-w-sm mx-4 text-center">
            <div class="relative flex items-center justify-center">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-indigo-100 border-t-indigo-600"></div>
                <svg class="h-6 w-6 text-indigo-600 absolute animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-extrabold text-slate-900">Verifying Payment</h3>
                <p class="text-xs text-slate-500 font-medium">Securing transaction parameters & generating your order invoice. Please do not close this window.</p>
            </div>
        </div>
    </div>

    <!-- Load Razorpay Standard Checkout SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

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
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-500 mb-1.5">Full Name</label>
                        <input id="name" type="text" wire:model="name" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-500 mb-1.5">Email Address</label>
                        <input id="email" type="email" wire:model="email" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
                        @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-500 mb-1.5">Phone Number (10 Digits)</label>
                        <input id="phone" type="text" wire:model="phone" placeholder="9876543210" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600" {{ !auth()->check() ? 'disabled' : '' }}>
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
                                    <input type="radio" wire:model="paymentMethod" value="razorpay" class="text-indigo-600 focus:ring-indigo-600">
                                    <span class="text-sm font-semibold text-slate-850">Razorpay (UPI / Cards / NetBanking)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-2 pt-6">
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled"
                            wire:target="placeOrder"
                            class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3.5 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2"
                            {{ !auth()->check() ? 'disabled' : '' }}
                        >
                            <span wire:loading.remove wire:target="placeOrder">
                                Place Order (₹{{ number_format($grandTotal) }})
                            </span>
                            <span wire:loading wire:target="placeOrder" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing Order...
                            </span>
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

    <!-- Client-Side Razorpay Modal Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('open-razorpay-checkout', event => {
                const data = event.detail[0] || event.detail;
                
                const options = {
                    "key": data.key,
                    "amount": data.amount,
                    "currency": "INR",
                    "name": "Saffron Store",
                    "description": "Order Payment",
                    "order_id": data.orderId,
                    "handler": function (response) {
                        // Find the Livewire component and call the verification method directly
                        const component = Livewire.find('{{ $this->getId() }}');
                        component.call(
                            'handleRazorpaySuccess', 
                            response.razorpay_payment_id, 
                            response.razorpay_signature, 
                            data.appOrderDbId
                        );
                    },
                    "prefill": {
                        "name": data.name,
                        "email": data.email,
                        "contact": data.phone
                    },
                    "theme": {
                        "color": "#4f46e5"
                    }
                };

                const rzp = new Razorpay(options);
                rzp.open();
            });
        });
    </script>
</div>