<?php

use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt(['email' => $this->email, 'password' => $this->password])) {
            if (auth()->user()->is_admin) {
                return redirect()->intended('/admin');
            }

            // Log out non-admin users attempting to login via admin panel
            auth()->logout();
            session()->flash('error', 'Access denied. You do not have administrator privileges.');
            return;
        }

        session()->flash('error', 'Invalid administrator credentials. Please try again.');
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm">
        <div class="text-center">
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-650 ring-1 ring-inset ring-indigo-200/50 mb-4">
                Control Panel
            </span>
            <h2 class="text-3xl font-extrabold text-slate-900">Admin Sign In</h2>
            <p class="text-xs text-slate-500 mt-2">Authorized administrator credentials required</p>
        </div>

        @if (session()->has('error'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700 leading-relaxed">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="login" class="space-y-6">
            <div>
                <label for="admin-email" class="block text-xs font-semibold text-slate-500 mb-1.5">Administrator Email</label>
                <input id="admin-email" type="email" wire:model="email" placeholder="admin@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="admin-password" class="block text-xs font-semibold text-slate-500 mb-1.5">Password</label>
                <input id="admin-password" type="password" wire:model="password" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                @error('password') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-slate-900 py-3 text-sm font-bold text-white shadow hover:bg-slate-850 transition duration-300">
                Authenticate Admin
            </button>
        </form>

        <div class="text-center border-t border-slate-100 pt-4">
            <a href="/" class="text-xs text-slate-500 hover:text-slate-900 font-medium">
                &larr; Return to Storefront
            </a>
        </div>
    </div>
</div>
