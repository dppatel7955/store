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
            $user = auth()->user();

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
                \Illuminate\Support\Facades\Log::info("Verification code for unverified login ({$user->email}): {$code}");
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerificationMail($code));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send login verification email: " . $e->getMessage());
                }

                // Log out user since they are not verified yet
                auth()->logout();
                session()->invalidate();
                session()->regenerateToken();

                // Redirect to verify-email portal
                return redirect()->route('verify-email', [
                    'email' => $this->email,
                    'redirect' => request()->query('redirect', '/shop')
                ]);
            }

            return redirect()->intended('/shop');
        }

        session()->flash('error', 'Invalid credentials. Please try again.');
    }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-slate-900">Sign In</h2>
            <p class="text-xs text-slate-500 mt-1">Sign in to checkout and review products</p>
        </div>

        @if (session()->has('error'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-550 mb-1.5">Email Address</label>
                <input type="email" wire:model="email" placeholder="you@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-555 mb-1.5">Password</label>
                <input type="password" wire:model="password" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                @error('password') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300">
                Sign In
            </button>
        </form>

        <div class="text-center border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-500 font-medium">
                Don't have an account? 
                <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:underline">Sign Up</a>
            </p>
        </div>
    </div>
</div>