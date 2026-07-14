<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component
{
    public string $email = '';

    protected function throttleKey(): string
    {
        return sprintf('forgot-password:%s:%s', strtolower($this->email ?: 'guest'), request()->ip());
    }

    public function sendResetLink()
    {
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many requests. Please try again in {$seconds} seconds.");
            return;
        }

        $this->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $this->email)->first();

        if ($user) {
            // Generate standard password reset token
            $token = Password::getRepository()->create($user);
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($resetUrl, $user->name));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send password reset email: " . $e->getMessage());
                $this->addError('email', 'Failed to dispatch email. Please ensure your SMTP configuration is active.');
                return;
            }
        }

        RateLimiter::hit($throttleKey, 300);
        session()->flash('success', 'Password reset instructions have been dispatched to your email address.');
        $this->email = '';
    }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h1 class="text-2xl font-extrabold text-slate-900">Forgot Password?</h1>
            <p class="text-xs text-slate-500 mt-1">Enter your registered email address to receive a secure password recovery link.</p>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="sendResetLink" class="space-y-4">
            <div>
                <label for="recovery_email" class="block text-xs font-semibold text-slate-550 mb-1.5">Email Address</label>
                <input type="email" id="recovery_email" wire:model="email" placeholder="you@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="sendResetLink">Send Recovery Link</span>
                <span wire:loading wire:target="sendResetLink" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending Link...
                </span>
            </button>
        </form>

        <div class="text-center border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-500 font-medium">
                Remember your credentials? 
                <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</div>
