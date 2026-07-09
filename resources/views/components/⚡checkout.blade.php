<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\VerificationCodeService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

new class extends Component
{
    public array $cart = [];
    public float $subtotal = 0;
    public float $shipping = 0;
    public float $grandTotal = 0;

    // Coupon fields
    public string $couponCode = '';
    public ?string $appliedCoupon = null;
    public float $discount = 0.00;

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
    public array $availablePaymentMethods = [];

    protected function throttleKey(string $action): string
    {
        $identity = auth()->check() ? (string) auth()->id() : strtolower($this->email ?: 'guest');
        return sprintf('checkout:%s:%s:%s', $action, $identity, request()->ip());
    }

    protected function currentProductPrice(Product $product): float
    {
        return (float) ($product->sale_price ?? $product->price);
    }

    protected function currentVariantPrice(ProductVariant $variant, Product $product): float
    {
        return (float) ($variant->sale_price ?? $variant->price ?? $this->currentProductPrice($product));
    }

    protected function syncCartFromDatabase(): bool
    {
        $validatedCart = [];
        $hasPricingChanges = false;
        $newSubtotal = 0.0;

        foreach ($this->cart as $cartKey => $item) {
            $product = Product::find($item['id'] ?? 0);
            if (!$product || !$product->is_active) {
                $this->addError('cart', "One of the products in your cart is unavailable. Please review your cart.");
                return false;
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                $this->addError('cart', 'Invalid cart quantity detected. Please refresh your cart.');
                return false;
            }

            $unitAmount = $this->currentProductPrice($product);
            $availableStock = (int) $product->stock;
            $variantName = null;

            if (!empty($item['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->find($item['variant_id']);
                if (!$variant || !$variant->is_active) {
                    $this->addError('cart', "A selected product variant is no longer available.");
                    return false;
                }

                $availableStock = (int) $variant->stock;
                $unitAmount = $this->currentVariantPrice($variant, $product);
                $variantName = $variant->name;
            }

            if ($quantity > $availableStock) {
                $this->addError('cart', "Insufficient stock for {$product->name}. Only {$availableStock} item(s) left.");
                return false;
            }

            $storedUnitAmount = (float) ($item['price'] ?? 0);
            if (round($storedUnitAmount, 2) !== round($unitAmount, 2)) {
                $hasPricingChanges = true;
            }

            $lineTotal = round($unitAmount * $quantity, 2);
            $newSubtotal += $lineTotal;

            $validatedCart[$cartKey] = array_merge($item, [
                'name' => $product->name,
                'slug' => $product->slug,
                'variant_name' => $variantName ?? ($item['variant_name'] ?? null),
                'price' => $unitAmount,
                'total' => $lineTotal,
            ]);
        }

        $this->cart = $validatedCart;
        $this->subtotal = round($newSubtotal, 2);

        if ($this->appliedCoupon) {
            $coupon = \App\Models\Coupon::where('code', $this->appliedCoupon)->first();
            if (!$coupon || !$coupon->isValidForAmount($this->subtotal, auth()->id())) {
                $this->appliedCoupon = null;
                $this->discount = 0.00;
                $this->couponCode = '';
            } else {
                $this->discount = (float) $coupon->calculateDiscountForCart($this->cart);
            }
        }

        $this->recalculateTotals();
        session()->put('cart', $this->cart);

        if ($hasPricingChanges) {
            $this->dispatch(
                'swal',
                title: 'Cart Updated',
                text: 'Prices were refreshed to match current catalog rates.',
                icon: 'info'
            );
        }

        return true;
    }

    public function mount()
    {
        $this->loadActivePaymentMethods();
        $this->cart = CartService::get();
        if (count($this->cart) === 0) {
            return redirect()->route('shop');
        }
        $this->subtotal = CartService::getSubtotal();
        if ($this->subtotal > 50000) {
            $this->shipping = 0.00;
        }
        $this->recalculateTotals();

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
            $this->phone = auth()->user()->phone ?? '';
        }
    }

    protected function loadActivePaymentMethods(): void
    {
        $methods = PaymentMethod::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->availablePaymentMethods = $methods->map(function (PaymentMethod $method) {
            return [
                'name' => $method->name,
                'code' => $method->code,
                'handler' => $method->handler,
                'description' => $method->description,
                'instructions' => $method->instructions,
            ];
        })->toArray();

        $allowedCodes = array_column($this->availablePaymentMethods, 'code');
        if (!empty($allowedCodes) && !in_array($this->paymentMethod, $allowedCodes, true)) {
            $this->paymentMethod = $allowedCodes[0];
        }
    }

    public function applyCoupon()
    {
        $this->resetErrorBag('couponCode');
        $code = strtoupper(trim($this->couponCode));

        if (empty($code)) {
            $this->addError('couponCode', 'Please enter a coupon code.');
            return;
        }

        $coupon = \App\Models\Coupon::where('code', $code)->first();

        if (!$coupon) {
            $this->addError('couponCode', 'Invalid coupon code.');
            return;
        }

        if (!$coupon->isValidForAmount($this->subtotal, auth()->id())) {
            if (!$coupon->is_active) {
                $this->addError('couponCode', 'This coupon is inactive.');
            } elseif ($coupon->isExpired()) {
                $this->addError('couponCode', 'This coupon has expired.');
            } elseif ($coupon->user_id && auth()->id() !== (int)$coupon->user_id) {
                $this->addError('couponCode', 'This coupon is restricted to a different user account.');
            } elseif ($coupon->min_order_amount && $this->subtotal < $coupon->min_order_amount) {
                $this->addError('couponCode', "Min order of ₹" . number_format($coupon->min_order_amount) . " required.");
            } else {
                $this->addError('couponCode', 'This coupon is not valid.');
            }
            return;
        }

        $calculatedDiscount = $coupon->calculateDiscountForCart($this->cart);

        if ($calculatedDiscount <= 0) {
            $this->addError('couponCode', 'Your cart does not contain qualifying products for this coupon.');
            return;
        }

        $this->appliedCoupon = $coupon->code;
        $this->discount = $calculatedDiscount;
        $this->recalculateTotals();

        $this->dispatch('swal', title: 'Coupon Applied!', text: "Discount of ₹" . number_format($this->discount) . " applied.", icon: 'success');
    }

    public function removeCoupon()
    {
        $this->appliedCoupon = null;
        $this->discount = 0.00;
        $this->couponCode = '';
        $this->recalculateTotals();
        $this->dispatch('swal', title: 'Coupon Removed!', text: 'Coupon has been removed.', icon: 'info');
    }

    public function recalculateTotals()
    {
        if ($this->subtotal > 50000) {
            $this->shipping = 0.00;
        }
        $this->grandTotal = max(0, $this->subtotal - $this->discount + $this->shipping);
    }

    public function placeOrder()
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $this->loadActivePaymentMethods();

        $user = auth()->user();

        // Enforce email verification before placing an order
        if ($user->email_verified_at === null) {
            $otpKey = $this->throttleKey('otp-send');
            if (RateLimiter::tooManyAttempts($otpKey, 3)) {
                $seconds = RateLimiter::availableIn($otpKey);
                $this->dispatch(
                    'swal',
                    title: 'Please Wait',
                    text: "Too many verification code requests. Try again in {$seconds} seconds.",
                    icon: 'warning'
                );
                return;
            }

            RateLimiter::hit($otpKey, 300);

            $verification = VerificationCodeService::issue('email_verify', $user->email);

            // Dispatch Email
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerificationMail($verification->code));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send checkout verification email: " . $e->getMessage());
            }

            // Redirect to verify-email portal with return path back to checkout page
            return redirect()->route('verify-email', [
                'email' => $user->email,
                'redirect' => route('checkout', [], false)
            ]);
        }

        if (!$this->syncCartFromDatabase()) {
            return;
        }

        if (empty($this->availablePaymentMethods)) {
            $this->addError('paymentMethod', 'No payment methods are currently available.');
            return;
        }

        $allowedCodes = array_column($this->availablePaymentMethods, 'code');

        $this->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'street' => 'required|string|min:5',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|numeric|digits:10',
            'paymentMethod' => ['required', 'string', Rule::in($allowedCodes)],
        ]);

        $selectedMethod = PaymentMethod::active()
            ->where('code', $this->paymentMethod)
            ->first();

        if (!$selectedMethod) {
            $this->addError('paymentMethod', 'Selected payment method is inactive or unavailable.');
            return;
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'coupon_code' => $this->appliedCoupon,
            'discount_amount' => $this->discount,
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
        if ($selectedMethod->handler === 'razorpay') {
            $keyId = $selectedMethod->gateway_key ?: config('services.razorpay.key_id');
            $keySecret = $selectedMethod->gateway_secret ?: config('services.razorpay.key_secret');

            if (empty($keyId) || empty($keySecret)) {
                \Illuminate\Support\Facades\Log::error("Razorpay API Keys are missing for payment method code: {$selectedMethod->code}");
                $this->dispatch('swal', 
                    title: 'Gateway Config Error', 
                    text: 'Razorpay credentials are not configured for this payment method. Please contact support.', 
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
        if ((int) $order->user_id !== (int) auth()->id()) {
            \Illuminate\Support\Facades\Log::warning("Unauthorized Razorpay callback attempt for order {$dbOrderId} by user " . auth()->id());
            $this->dispatch(
                'swal',
                title: 'Unauthorized Request',
                text: 'This order does not belong to your account.',
                icon: 'error'
            );
            return;
        }

        $methodConfig = PaymentMethod::where('code', $order->payment_method)->first();
        $keySecret = $methodConfig?->gateway_secret ?: config('services.razorpay.key_secret');
        if (empty($keySecret)) {
            \Illuminate\Support\Facades\Log::error("Missing Razorpay secret for order {$dbOrderId} and method {$order->payment_method}");
            $this->dispatch(
                'swal',
                title: 'Payment Verification Failed',
                text: 'Gateway configuration is missing. Please contact support.',
                icon: 'error'
            );
            return;
        }
        
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
    <div wire:loading wire:target="handleRazorpaySuccess" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/70 backdrop-blur-md transition-all duration-300" style="display: none;">
        <div class="bg-white/95 border border-slate-200/50 p-6 sm:p-10 rounded-3xl shadow-2xl flex flex-col items-center space-y-6 w-[90%] max-w-sm sm:max-w-md text-center transform scale-100 transition-all duration-300">
            <!-- Premium Spinner Wrapper -->
            <div class="relative flex items-center justify-center h-20 w-20">
                <!-- Outer Pulse Ring -->
                <div class="absolute inset-0 rounded-full bg-indigo-500/10 animate-ping"></div>
                <!-- Gradient Border Spinner -->
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-slate-100 border-t-indigo-650 border-r-indigo-650"></div>
                <!-- Shield Icon -->
                <svg class="h-6 w-6 text-indigo-650 absolute animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="space-y-2">
                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">Verifying Payment</h3>
                <p class="text-xs sm:text-sm text-slate-500 font-medium leading-relaxed">Securing transaction parameters & generating your order invoice.<br><span class="text-indigo-600 font-bold">Please do not close or refresh this page.</span></p>
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

                @error('cart')
                    <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700">
                        {{ $message }}
                    </div>
                @enderror

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
                        @if(count($availablePaymentMethods) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($availablePaymentMethods as $method)
                                    <label class="flex items-start justify-between border border-slate-200 bg-slate-50 p-4 rounded-xl cursor-pointer hover:border-indigo-600 hover:shadow-sm transition select-none">
                                        <span class="flex items-start gap-3">
                                            <input type="radio" wire:model="paymentMethod" value="{{ $method['code'] }}" class="mt-1 text-indigo-600 focus:ring-indigo-600">
                                            <span class="space-y-1">
                                                <span class="text-sm font-semibold text-slate-850">{{ $method['name'] }}</span>
                                                @if(!empty($method['description']))
                                                    <span class="block text-xs text-slate-500">{{ $method['description'] }}</span>
                                                @endif
                                                @if(!empty($method['instructions']))
                                                    <span class="block text-[11px] text-indigo-700 font-medium">{{ $method['instructions'] }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-xs font-semibold text-amber-700">
                                No active payment methods are available. Please contact support.
                            </div>
                        @endif
                        @error('paymentMethod') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2 pt-6">
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled"
                            wire:target="placeOrder"
                            class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3.5 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2"
                            {{ (!auth()->check() || count($availablePaymentMethods) === 0) ? 'disabled' : '' }}
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
                            <div class="text-right flex flex-col items-end">
                                <span class="text-xs font-bold text-slate-900">₹{{ number_format($item['total']) }}</span>
                                @if(isset($item['original_price']) && $item['price'] < $item['original_price'])
                                    <span class="text-[9px] text-slate-400 line-through">₹{{ number_format($item['original_price'] * $item['quantity']) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Promo Code / Coupon -->
                <div class="border-t border-slate-200 pt-4 space-y-2">
                    <label class="block text-xs font-semibold text-slate-500">Have a promo code?</label>
                    @if($appliedCoupon)
                        <div class="flex items-center justify-between bg-indigo-50 border border-indigo-150 rounded-xl p-2.5">
                            <div class="flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider font-mono">{{ $appliedCoupon }}</span>
                            </div>
                            <button type="button" wire:click="removeCoupon" class="text-xs font-bold text-rose-600 hover:text-rose-700">Remove</button>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                wire:model="couponCode" 
                                placeholder="e.g. SAVE20" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition font-mono uppercase"
                            />
                            <button 
                                type="button" 
                                wire:click="applyCoupon" 
                                class="rounded-xl border border-indigo-600 bg-indigo-50 text-indigo-705 px-4 py-2 text-xs font-bold hover:bg-indigo-100 transition whitespace-nowrap"
                            >
                                Apply
                            </button>
                        </div>
                        @error('couponCode') <span class="text-[10px] text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    @endif
                </div>

                <div class="border-t border-slate-200 pt-4 space-y-2.5 text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-800">₹{{ number_format($subtotal) }}</span>
                    </div>
                    @if($discount > 0)
                        <div class="flex justify-between text-emerald-700 font-bold">
                            <span>Discount</span>
                            <span>- ₹{{ number_format($discount) }}</span>
                        </div>
                    @endif
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