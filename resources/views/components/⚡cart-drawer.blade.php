<?php

use Livewire\Component;
use App\Services\CartService;
use Livewire\Attributes\On;

new class extends Component
{
    public bool $isOpen = false;
    public array $cart = [];
    public float $subtotal = 0;

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
             class="w-screen max-w-md bg-white border-l border-slate-205 shadow-2xl flex flex-col">
             
             <!-- Header -->
             <div class="px-4 py-6 bg-white border-b border-slate-200 flex items-center justify-between sm:px-6">
                 <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                     <svg class="h-5 w-5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                     </svg>
                     Your Cart
                 </h2>
                 <button @click="open = false" class="p-2 -mr-1 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-50 focus:outline-none" aria-label="Close cart">
                     <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>

             <!-- Cart Items -->
             <div class="flex-1 overflow-y-auto py-6 px-4 sm:px-6">
                 @if(count($cart) > 0)
                     <div class="space-y-6">
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
                                                  <a href="{{ route('shop') }}" @click="open = false" class="hover:text-indigo-650 transition">{{ $item['name'] }}</a>
                                              </h3>
                                              <span class="text-xs font-extrabold text-slate-900 whitespace-nowrap">₹{{ number_format($item['total']) }}</span>
                                          </div>
                                          <p class="text-[10px] text-slate-450 mt-0.5">Price: ₹{{ number_format($item['price']) }}</p>
                                          @if(!empty($item['variant_name']))
                                              <p class="text-[10px] text-indigo-600 font-medium mt-0.5">Variant: {{ $item['variant_name'] }}</p>
                                          @endif
                                      </div>

                                      <!-- Quantity & Remove -->
                                      <div class="flex items-center justify-between mt-3">
                                          <!-- Quantity selector -->
                                          <div class="flex items-center gap-0.5 bg-white border border-slate-200 rounded-xl p-0.5 shadow-sm">
                                              <button wire:click="decreaseQuantity('{{ $key }}')" class="h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-slate-50 text-slate-600 transition" aria-label="Decrease quantity">
                                                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                                                  </svg>
                                              </button>
                                              <span class="text-xs font-bold text-slate-800 px-2 min-w-[1.5rem] text-center">{{ $item['quantity'] }}</span>
                                              <button wire:click="increaseQuantity('{{ $key }}')" class="h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-slate-50 text-slate-600 transition" aria-label="Increase quantity">
                                                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                                  </svg>
                                              </button>
                                          </div>
                                          
                                          <button wire:click="removeItem('{{ $key }}')" class="min-h-9 px-2 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
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
                         <p class="text-slate-500 font-medium">Your cart is empty.</p>
                         <a href="{{ route('shop') }}" @click="open = false" class="inline-block mt-4 text-xs font-semibold text-indigo-650 hover:underline">Start shopping</a>
                     </div>
                 @endif
             </div>

             <!-- Footer -->
             @if(count($cart) > 0)
                 <div class="border-t border-slate-200 bg-slate-50/60 p-4 sm:p-6 pb-[max(1rem,env(safe-area-inset-bottom))] space-y-4">
                     <div class="flex justify-between text-base font-semibold text-slate-800">
                         <span>Subtotal</span>
                         <span>₹{{ number_format($subtotal) }}</span>
                     </div>
                     <p class="text-xs text-slate-500">Shipping and taxes will be calculated at checkout.</p>
                     
                     <div class="pt-2">
                         <a href="{{ route('checkout') }}" class="flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300">
                             Checkout
                         </a>
                     </div>
                 </div>
             @endif
        </div>
    </div>
</div>