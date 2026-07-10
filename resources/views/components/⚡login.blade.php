<?php

use Livewire\Component;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component
{
    public string $email = '';
    public string $password = '';

    protected function throttleKey(string $action): string
    {
        return sprintf('login:%s:%s:%s', $action, strtolower($this->email ?: 'guest'), request()->ip());
    }

    public function login()
    {
        $loginKey = $this->throttleKey('attempt');
        if (RateLimiter::tooManyAttempts($loginKey, 5)) {
            $seconds = RateLimiter::availableIn($loginKey);
            $this->addError('email', "Too many login attempts. Try again in {$seconds} seconds.");
            return;
        }

        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::clear($loginKey);
            $user = auth()->user();

            if ($user->email_verified_at === null) {
                $otpKey = $this->throttleKey('otp-send');
                if (RateLimiter::tooManyAttempts($otpKey, 3)) {
                    $seconds = RateLimiter::availableIn($otpKey);
                    auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    session()->flash('error', "Too many verification code requests. Try again in {$seconds} seconds.");
                    return;
                }

                RateLimiter::hit($otpKey, 300);

                $verification = VerificationCodeService::issue('email_verify', $user->email);

                // Dispatch Email
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerificationMail($verification->code));
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

        RateLimiter::hit($loginKey, 300);
        session()->flash('error', 'Invalid credentials. Please try again.');
    }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h1 class="text-2xl font-extrabold text-slate-900">Sign In</h1>
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

            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="login">Sign In</span>
                <span wire:loading wire:target="login" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing In...
                </span>
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