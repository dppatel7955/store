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

    public function increaseQuantity($productId)
    {
        if (isset($this->cart[$productId])) {
            $qty = $this->cart[$productId]['quantity'] + 1;
            CartService::update($productId, $qty);
            $this->dispatch('cart-updated');
        }
    }

    public function decreaseQuantity($productId)
    {
        if (isset($this->cart[$productId])) {
            $qty = $this->cart[$productId]['quantity'] - 1;
            CartService::update($productId, $qty);
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem($productId)
    {
        CartService::remove($productId);
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
                 <button @click="open = false" class="text-slate-500 hover:text-slate-805 focus:outline-none">
                     <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>

             <!-- Cart Items -->
             <div class="flex-1 overflow-y-auto py-6 px-4 sm:px-6">
                 @if(count($cart) > 0)
                     <div class="space-y-6">
                         @foreach($cart as $item)
                             <div class="flex items-center gap-4 bg-slate-50/70 p-3 rounded-xl border border-slate-200/60">
                                 <!-- Thumbnail -->
                                 <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-white border border-slate-200">
                                     <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                 </div>
                                 <!-- Item details -->
                                 <div class="flex-1">
                                     <h3 class="text-sm font-semibold text-slate-800 line-clamp-1">
                                         <a href="/shop" @click="open = false" class="hover:text-indigo-600 transition">{{ $item['name'] }}</a>
                                     </h3>
                                     <p class="text-xs text-slate-550 mt-0.5">Price: ₹{{ number_format($item['price']) }}</p>
                                     
                                     <!-- Quantity selector -->
                                     <div class="flex items-center gap-2 mt-2">
                                         <button wire:click="decreaseQuantity({{ $item['id'] }})" class="p-1 rounded bg-slate-205/60 hover:bg-slate-200 text-slate-700 transition">
                                             <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                                             </svg>
                                         </button>
                                         <span class="text-xs font-semibold text-slate-800 px-1">{{ $item['quantity'] }}</span>
                                         <button wire:click="increaseQuantity({{ $item['id'] }})" class="p-1 rounded bg-slate-205/60 hover:bg-slate-200 text-slate-700 transition">
                                             <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                             </svg>
                                         </button>
                                     </div>
                                 </div>
                                 <!-- Actions & Total -->
                                 <div class="text-right">
                                     <span class="text-sm font-bold text-slate-900">₹{{ number_format($item['total']) }}</span>
                                     <button wire:click="removeItem({{ $item['id'] }})" class="block text-xs text-rose-605 hover:text-rose-700 mt-2 ml-auto transition">
                                         Remove
                                     </button>
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
                 <div class="border-t border-slate-200 bg-slate-50/60 p-4 sm:p-6 space-y-4">
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