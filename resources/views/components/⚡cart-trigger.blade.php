<?php

use Livewire\Component;
use App\Services\CartService;
use Livewire\Attributes\On;

new class extends Component
{
    public int $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('cart-updated')]
    public function updateCount()
    {
        $this->count = CartService::getCount();
    }

    public function toggleCart()
    {
        $this->dispatch('toggle-cart-drawer');
    }
};
?>

<button wire:click="toggleCart" class="relative p-2 text-slate-300 hover:text-black transition focus:outline-none">
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
    </svg>
    @if($count > 0)
        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-pink-500 to-rose-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center border border-slate-900 shadow animate-pulse">
            {{ $count }}
        </span>
    @endif
</button>