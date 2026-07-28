<?php

use Livewire\Component;
use App\Services\CartService;
use App\Models\Coupon;
use Livewire\Attributes\On;

new class extends Component
{
    public bool $isOpen = false;
    public array $cart = [];
    public float $subtotal = 0;

    // Free Shipping Progress
    public float $freeShippingThreshold = 999;

    // Promo Coupon state
    public string $couponInput = '';
    public ?string $appliedCouponCode = null;
    public float $discount = 0;
    public string $couponError = '';
    public string $couponSuccessMessage = '';

    // Location Shipping Estimator
    public string $estimatorLocation = '';

    public function mount()
    {
        $this->refreshCart();
    }

    #[On('toggle-cart-drawer')]
    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->refreshCart();
        }
    }

    #[On('cart-updated')]
    public function refreshCart()
    {
        $this->cart = CartService::get();
        $this->subtotal = CartService::getSubtotal();
        $this->appliedCouponCode = session()->get('applied_coupon_code');

        $this->recalculateDiscount();
        $this->recalculateFreeShippingThreshold();
    }

    public function recalculateFreeShippingThreshold()
    {
        $globalThreshold = \App\Services\HomeSettingsService::globalFreeShippingThreshold();

        if (empty($this->cart)) {
            $this->freeShippingThreshold = $globalThreshold;
            return;
        }

        $thresholds = [];
        foreach ($this->cart as $item) {
            $product = \App\Models\Product::find($item['id'] ?? 0);
            if ($product) {
                $thresholds[] = $product->getEffectiveFreeShippingThreshold();
            }
        }

        if (! empty($thresholds)) {
            $this->freeShippingThreshold = (float) min($thresholds);
        } else {
            $this->freeShippingThreshold = $globalThreshold;
        }
    }

    public function recalculateDiscount()
    {
        $this->discount = 0;
        $this->couponError = '';

        if ($this->appliedCouponCode) {
            $coupon = Coupon::where('code', $this->appliedCouponCode)->first();
            if ($coupon && $coupon->isValidForAmount($this->subtotal, auth()->id())) {
                $this->discount = (float) $coupon->calculateDiscountForCart($this->cart);
            } else {
                session()->forget('applied_coupon_code');
                $this->appliedCouponCode = null;
            }
        }
    }

    public function applyCoupon()
    {
        $this->couponError = '';
        $this->couponSuccessMessage = '';
        $code = strtoupper(trim($this->couponInput));

        if (empty($code)) {
            $this->couponError = 'Please enter a coupon code.';
            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            $this->couponError = 'Invalid coupon code.';
            return;
        }

        if (! $coupon->isValidForAmount($this->subtotal, auth()->id())) {
            if (! $coupon->is_active) {
                $this->couponError = 'This coupon is inactive.';
            } elseif ($coupon->isExpired()) {
                $this->couponError = 'This coupon has expired.';
            } elseif ($coupon->min_order_amount && $this->subtotal < $coupon->min_order_amount) {
                $this->couponError = 'Minimum order of ₹' . number_format($coupon->min_order_amount) . ' required.';
            } else {
                $this->couponError = 'This coupon is not valid for your order.';
            }
            return;
        }

        $calculatedDiscount = $coupon->calculateDiscountForCart($this->cart);
        if ($calculatedDiscount <= 0) {
            $this->couponError = 'Cart does not contain qualifying products for this coupon.';
            return;
        }

        session()->put('applied_coupon_code', $coupon->code);
        $this->appliedCouponCode = $coupon->code;
        $this->discount = $calculatedDiscount;
        $this->couponInput = '';
        $this->couponSuccessMessage = 'Coupon Applied! Saved ₹' . number_format($calculatedDiscount);
    }

    public function removeCoupon()
    {
        session()->forget('applied_coupon_code');
        $this->appliedCouponCode = null;
        $this->discount = 0;
        $this->couponError = '';
        $this->couponSuccessMessage = '';
    }

    public function increaseQuantity(string $cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            $qty = $this->cart[$cartKey]['quantity'] + 1;
            CartService::update($cartKey, $qty);
            $this->dispatch('cart-updated');
        }
    }

    public function decreaseQuantity(string $cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            $qty = $this->cart[$cartKey]['quantity'] - 1;
            CartService::update($cartKey, $qty);
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem(string $cartKey)
    {
        CartService::remove($cartKey);
        $this->dispatch('cart-updated');
    }

    public function closeDrawer()
    {
        $this->isOpen = false;
    }
};
?>

<div x-data="{ open: @entangle('isOpen') }" x-show="open" class="relative z-50" style="display: none;">
    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"></div>

    <!-- Drawer Panel Container -->
    <div class="fixed inset-y-0 right-0 max-w-full flex pl-4 sm:pl-10">
        <!-- Drawer Panel -->
        <div x-show="open"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-screen max-w-md bg-white border-l border-slate-200 shadow-2xl flex flex-col">
             
             <!-- Header -->
             <div class="px-4 py-5 bg-white border-b border-slate-200 flex items-center justify-between sm:px-6">
                 <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                     <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                     </svg>
                     Your Shopping Cart
                 </h2>
                 <button @click="open = false" class="p-2 -mr-1 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-50 focus:outline-none" aria-label="Close cart">
                     <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>

             <!-- Free Shipping Progress Bar -->
             @if(count($cart) > 0)
                 @php
                     $progressPercent = min(100, round(($subtotal / $freeShippingThreshold) * 100));
                     $remainingAmount = max(0, $freeShippingThreshold - $subtotal);
                 @endphp
                 <div class="bg-indigo-50/70 border-b border-indigo-100 p-4 space-y-2">
                     <div class="flex items-center justify-between text-xs font-bold">
                         @if($remainingAmount == 0)
                             <span class="text-emerald-700 flex items-center gap-1.5">
                                 <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                 </svg>
                                 🎉 You've unlocked FREE Express Shipping!
                             </span>
                         @else
                             <span class="text-slate-800 flex items-center gap-1.5">
                                 <span>🚚</span> Add <strong class="text-indigo-600 font-extrabold">₹{{ number_format($remainingAmount) }}</strong> more for FREE Shipping!
                             </span>
                             <span class="text-[11px] text-slate-500 font-semibold">{{ $progressPercent }}%</span>
                         @endif
                     </div>
                     <!-- Progress Track -->
                     <div class="h-2 w-full bg-slate-200/80 rounded-full overflow-hidden shadow-inner">
                         <div 
                             class="h-full rounded-full transition-all duration-500 ease-out {{ $remainingAmount == 0 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-amber-500 via-indigo-500 to-purple-600' }}"
                             style="width: {{ $progressPercent }}%"
                         ></div>
                     </div>
                 </div>
             @endif

             <!-- Cart Items -->
             <div class="flex-1 overflow-y-auto py-6 px-4 sm:px-6">
                 @if(count($cart) > 0)
                     <div class="space-y-4">
                          @foreach($cart as $key => $item)
                              <div class="flex gap-4 bg-slate-50/70 p-3 rounded-xl border border-slate-200/60">
                                  <!-- Thumbnail -->
                                  <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg bg-white border border-slate-200">
                                      <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                  </div>
                                  
                                  <!-- Info & Controls Container -->
                                  <div class="flex-1 flex flex-col justify-between">
                                      <div>
                                          <div class="flex justify-between items-start gap-2">
                                              <h3 class="text-xs font-bold text-slate-800 line-clamp-2">
                                                  <a href="{{ route('shop') }}" @click="open = false" class="hover:text-indigo-600 transition">{{ $item['name'] }}</a>
                                              </h3>
                                              <span class="text-xs font-extrabold text-slate-900 whitespace-nowrap">₹{{ number_format($item['total']) }}</span>
                                          </div>
                                          <p class="text-[10px] text-slate-500 mt-0.5">Price: ₹{{ number_format($item['price']) }}</p>
                                          @if(!empty($item['variant_name']))
                                              <p class="text-[10px] text-indigo-600 font-medium mt-0.5">Option: {{ $item['variant_name'] }}</p>
                                          @endif
                                      </div>

                                      <!-- Quantity & Remove -->
                                      <div class="flex items-center justify-between mt-3">
                                          <!-- Quantity selector -->
                                          <div class="flex items-center gap-0.5 bg-white border border-slate-200 rounded-xl p-0.5 shadow-sm">
                                              <button wire:click="decreaseQuantity('{{ $key }}')" class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-slate-50 text-slate-600 transition" aria-label="Decrease quantity">
                                                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                                                  </svg>
                                              </button>
                                              <span class="text-xs font-bold text-slate-800 px-2 min-w-[1.5rem] text-center">{{ $item['quantity'] }}</span>
                                              <button wire:click="increaseQuantity('{{ $key }}')" class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-slate-50 text-slate-600 transition" aria-label="Increase quantity">
                                                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                                  </svg>
                                              </button>
                                          </div>
                                          
                                          <button wire:click="removeItem('{{ $key }}')" class="min-h-8 px-2 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
                                              Remove
                                          </button>
                                      </div>
                                  </div>
                              </div>
                          @endforeach
                     </div>
                 @else
                     <div class="text-center py-12">
                         <svg class="h-12 w-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                         </svg>
                         <p class="text-slate-500 font-medium">Your cart is currently empty.</p>
                         <a href="{{ route('shop') }}" @click="open = false" class="inline-block mt-4 text-xs font-bold text-indigo-600 hover:underline">Start shopping</a>
                     </div>
                 @endif
             </div>

             <!-- Footer -->
             @if(count($cart) > 0)
                 <div class="border-t border-slate-200 bg-slate-50/80 p-4 sm:p-5 space-y-3">
                     <!-- Coupon Form Section -->
                     <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm space-y-2">
                         @if($appliedCouponCode)
                             <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200/80 rounded-lg py-2 px-3">
                                 <div class="flex items-center gap-2">
                                     <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                     </svg>
                                     <span class="text-xs font-bold text-emerald-800 tracking-wide font-mono uppercase">{{ $appliedCouponCode }}</span>
                                 </div>
                                 <button wire:click="removeCoupon" type="button" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline">
                                     Remove
                                 </button>
                             </div>
                         @else
                             <div class="flex items-center gap-2">
                                 <input 
                                     type="text" 
                                     wire:model="couponInput"
                                     placeholder="Promo Code (e.g. WELCOME10)" 
                                     class="flex-1 bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-xs uppercase tracking-wider text-slate-800 placeholder:normal-case placeholder:tracking-normal placeholder:text-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white transition"
                                 />
                                 <button 
                                     wire:click="applyCoupon" 
                                     type="button" 
                                     class="rounded-lg bg-indigo-600 hover:bg-indigo-500 py-2 px-3 text-xs font-bold text-white transition shadow-sm"
                                 >
                                     Apply
                                 </button>
                             </div>
                             @if($couponError)
                                 <p class="text-[11px] font-semibold text-rose-600">{{ $couponError }}</p>
                             @endif
                             @if($couponSuccessMessage)
                                 <p class="text-[11px] font-semibold text-emerald-600">{{ $couponSuccessMessage }}</p>
                             @endif
                         @endif
                     </div>

                     <!-- Location Shipping Rate Estimator -->
                     <div class="bg-slate-50/80 border border-slate-200/80 rounded-xl p-2.5 space-y-2">
                         <div class="flex items-center justify-between">
                             <span class="text-[11px] font-semibold text-slate-700 flex items-center gap-1">
                                 <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                 </svg>
                                 Estimate Shipping & Delivery
                             </span>
                         </div>
                         <div class="flex items-center gap-2">
                             <input 
                                 type="text" 
                                 wire:model.live.debounce.300ms="estimatorLocation"
                                 placeholder="Enter City or Pincode" 
                                 class="flex-1 bg-white border border-slate-200 rounded-lg py-1.5 px-2.5 text-xs text-slate-800 focus:outline-none focus:border-indigo-600 transition"
                             />
                         </div>
                         @if(trim($estimatorLocation) !== '')
                             @php
                                 $est = \App\Services\ShippingService::calculateRate($estimatorLocation, $estimatorLocation, $estimatorLocation, $subtotal, $freeShippingThreshold);
                             @endphp
                             <div class="text-[11px] font-semibold flex items-center justify-between text-slate-700 bg-white p-2 rounded-lg border border-slate-100">
                                 <span class="text-slate-500">Zone: <strong class="text-slate-800">{{ $est['zone_name'] }}</strong></span>
                                 @if($est['is_free'])
                                     <span class="text-emerald-600 font-extrabold bg-emerald-50 px-2 py-0.5 rounded">FREE Shipping</span>
                                 @else
                                     <span class="text-slate-900 font-extrabold">₹{{ number_format($est['charge'], 2) }}</span>
                                 @endif
                             </div>
                         @endif
                     </div>

                     <!-- Order Summary Totals -->
                     <div class="space-y-1.5 pt-1 text-xs">
                         <div class="flex justify-between text-slate-600 font-medium">
                             <span>Subtotal</span>
                             <span>₹{{ number_format($subtotal) }}</span>
                         </div>

                         @if($discount > 0)
                             <div class="flex justify-between text-emerald-600 font-bold">
                                 <span>Discount</span>
                                 <span>-₹{{ number_format($discount) }}</span>
                             </div>
                         @endif

                         <div class="flex justify-between text-slate-600 font-medium">
                             <span>Shipping</span>
                             @if($subtotal >= $freeShippingThreshold)
                                 <span class="text-emerald-600 font-bold uppercase text-[11px]">FREE</span>
                             @else
                                 <span>₹50</span>
                             @endif
                         </div>

                         <div class="flex justify-between text-base font-extrabold text-slate-900 pt-2 border-t border-slate-200">
                             <span>Total</span>
                             @php
                                 $shippingCost = $subtotal >= $freeShippingThreshold ? 0 : 50;
                                 $finalTotal = max(0, $subtotal - $discount + $shippingCost);
                             @endphp
                             <span class="text-indigo-600">₹{{ number_format($finalTotal) }}</span>
                         </div>
                     </div>
                     
                     <div class="pt-1">
                         <a href="{{ route('checkout') }}" class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300">
                             Proceed to Checkout
                             <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                             </svg>
                         </a>
                     </div>
                 </div>
             @endif
        </div>
    </div>
</div>