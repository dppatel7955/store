<?php

use Livewire\Component;
use App\Models\User;
use App\Models\VerificationCode;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component
{
    public string $email = '';
    public string $enteredOtp = '';
    public string $redirect = '/shop';

    public function mount()
    {
        $this->email = (string) request()->query('email', '');
        $this->redirect = $this->sanitizeRedirect((string) request()->query('redirect', '/shop'));
        
        // If email is empty, check if user is already logged in
        if (empty($this->email) && auth()->check()) {
            $this->email = auth()->user()->email;
        }
    }

    protected function sanitizeRedirect(string $redirect): string
    {
        $path = trim($redirect);
        if ($path === '' || str_starts_with($path, '//')) {
            return '/shop';
        }

        $parsed = parse_url($path);
        if (($parsed['scheme'] ?? null) || ($parsed['host'] ?? null)) {
            return '/shop';
        }

        return str_starts_with($path, '/') ? $path : '/shop';
    }

    protected function throttleKey(string $action): string
    {
        return sprintf('verify-email:%s:%s:%s', $action, strtolower($this->email ?: 'guest'), request()->ip());
    }

    public function verify()
    {
        $verifyKey = $this->throttleKey('verify');
        if (RateLimiter::tooManyAttempts($verifyKey, 10)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            $this->addError('enteredOtp', "Too many verification attempts. Try again in {$seconds} seconds.");
            return;
        }

        RateLimiter::hit($verifyKey, 300);

        $this->validate([
            'email' => 'required|email|exists:users,email',
            'enteredOtp' => 'required|numeric|digits:6',
        ]);

        // Fetch verification record
        $verification = VerificationCode::where('type', 'email_verify')
            ->where('identifier', $this->email)
            ->first();

        if (!$verification || !$verification->isValid($this->enteredOtp)) {
            if ($verification && $verification->isExpired()) {
                $this->addError('enteredOtp', 'The verification code has expired. Please request a new code.');
            } else {
                $this->addError('enteredOtp', 'The verification code entered is incorrect or expired.');
            }
            return;
        }

        // Mark code as verified
        $verification->update(['verified_at' => now()]);

        // Mark user as verified
        $user = User::where('email', $this->email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();
            
            // Login user if not authenticated
            if (!auth()->check() || auth()->user()->id !== $user->id) {
                auth()->login($user);
            }
        }

        session()->flash('success', 'Email verified successfully!');
        
        return redirect()->to($this->redirect);
    }

    public function resend()
    {
        $resendKey = $this->throttleKey('resend');
        if (RateLimiter::tooManyAttempts($resendKey, 3)) {
            $seconds = RateLimiter::availableIn($resendKey);
            $this->addError('email', "Too many resend requests. Try again in {$seconds} seconds.");
            return;
        }

        RateLimiter::hit($resendKey, 300);

        $this->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate new verification code
        $code = (string) rand(100000, 999999);

        // Save code to database
        VerificationCode::updateOrCreate(
            ['type' => 'email_verify', 'identifier' => $this->email],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
            ]
        );

        // Send Email
        try {
            Mail::to($this->email)->send(new VerificationMail($code));
            
            $this->dispatch('swal', 
                title: 'Code Sent!', 
                text: 'A new 6-digit verification code has been sent to your email.', 
                icon: 'success',
                toast: true
            );
        } catch (\Exception $e) {
            Log::error("Failed to send verification email: " . $e->getMessage());
            $this->dispatch('swal', 
                title: 'Mailer Error', 
                text: 'Unable to send email. Please check your SMTP settings.', 
                icon: 'error',
                toast: false
            );
        }
    }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-slate-900">Verify Email</h2>
            <p class="text-xs text-slate-500 mt-1">Verify your email address to log in or complete your order</p>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="verify" class="space-y-4">
            <div>
                <label for="verify-email-input" class="block text-xs font-semibold text-slate-500 mb-1.5">Email Address</label>
                <input id="verify-email-input" type="email" wire:model="email" placeholder="you@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition" @if(!empty(request()->query('email'))) readonly @endif>
                @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-xs text-indigo-700 leading-relaxed space-y-2">
                <p>We've sent a 6-digit verification code to the email address above. Please enter it below to verify your account.</p>
            </div>

            <div>
                <label for="verify-otp-input" class="block text-xs font-semibold text-slate-500 mb-1.5">Verification Code (6 Digits)</label>
                <input id="verify-otp-input" type="text" wire:model="enteredOtp" placeholder="123456" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition tracking-widest text-center text-lg font-bold">
                @error('enteredOtp') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs">
                <button type="button" wire:click="resend" wire:loading.attr="disabled" wire:target="resend" class="font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="resend">Resend Code</span>
                    <span wire:loading wire:target="resend" class="flex items-center gap-1.5 text-indigo-500">
                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sending...
                    </span>
                </button>
                <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-600">Back to Login</a>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="verify" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="verify">Verify & Continue</span>
                <span wire:loading wire:target="verify" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Verifying...
                </span>
            </button>
        </form>
    </div>
</div>
