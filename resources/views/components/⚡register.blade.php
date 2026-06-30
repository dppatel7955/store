<?php

use Livewire\Component;
use App\Models\User;
use App\Models\VerificationCode;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';

    // OTP State
    public bool $otpSent = false;
    public string $generatedOtp = '';
    public string $enteredOtp = '';

    /**
     * Dispatch verification code email to customer.
     */
    protected function sendVerificationEmail(string $email, string $code)
    {
        // Log locally as a backup
        Log::info("Verification code for customer registration (Email: {$email}): {$code}");

        try {
            Mail::to($email)->send(new VerificationMail($code));
        } catch (\Exception $e) {
            Log::error("Failed to send verification email to {$email}: " . $e->getMessage());
            $this->dispatch('swal', 
                title: 'SMTP Dispatch Error', 
                text: 'Unable to send verification email. Please contact support or verify your email settings.', 
                icon: 'error',
                toast: false
            );
        }
    }

    public function sendOtp()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric|digits:10|unique:users,phone',
            'password' => 'required|string|min:6',
        ]);

        // Generate OTP code
        $this->generatedOtp = (string) rand(100000, 999999);
        $this->otpSent = true;

        // Store OTP in database
        VerificationCode::updateOrCreate(
            ['type' => 'email_verify', 'identifier' => $this->email],
            [
                'code' => $this->generatedOtp,
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
            ]
        );

        // Dispatch Email
        $this->sendVerificationEmail($this->email, $this->generatedOtp);

        $this->dispatch('swal', 
            title: 'Verification Code Sent!', 
            text: 'A 6-digit verification code has been sent to your email address.', 
            icon: 'success',
            toast: true
        );
    }

    public function verifyAndRegister()
    {
        $this->validate([
            'enteredOtp' => 'required|numeric|digits:6',
        ]);

        // Fetch and validate OTP from database
        $verification = VerificationCode::where('type', 'email_verify')
            ->where('identifier', $this->email)
            ->first();

        if (!$verification || !$verification->isValid($this->enteredOtp)) {
            if ($verification && $verification->isExpired()) {
                $this->addError('enteredOtp', 'The verification code has expired. Please resend code.');
            } else {
                $this->addError('enteredOtp', 'The entered verification code is incorrect or has already been used.');
            }
            return;
        }

        // Mark OTP as verified
        $verification->update(['verified_at' => now()]);

        // Create Customer User
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'is_admin' => false,
            'email_verified_at' => now()
        ]);

        auth()->login($user);

        // Flash success message
        session()->flash('success', 'Account created and verified successfully!');
        
        return redirect()->intended('/shop');
    }

    public function resendOtp()
    {
        $this->generatedOtp = (string) rand(100000, 999999);
        $this->enteredOtp = '';

        // Store new OTP in database
        VerificationCode::updateOrCreate(
            ['type' => 'email_verify', 'identifier' => $this->email],
            [
                'code' => $this->generatedOtp,
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
            ]
        );

        // Dispatch new Email
        $this->sendVerificationEmail($this->email, $this->generatedOtp);
        
        $this->dispatch('swal', 
            title: 'Verification Code Resent!', 
            text: 'A new 6-digit verification code has been sent to your email address.', 
            icon: 'success',
            toast: true
        );
    }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-slate-900">Create Account</h2>
            <p class="text-xs text-slate-500 mt-1">Register to start shopping and tracking your orders</p>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(!$otpSent)
            <!-- Step 1: Input Registration Details -->
            <form wire:submit="sendOtp" class="space-y-4">
                <div>
                    <label for="reg-name" class="block text-xs font-semibold text-slate-500 mb-1.5">Full Name</label>
                    <input id="reg-name" type="text" wire:model="name" placeholder="John Doe" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                    @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="reg-email" class="block text-xs font-semibold text-slate-500 mb-1.5">Email Address</label>
                    <input id="reg-email" type="email" wire:model="email" placeholder="you@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                    @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="reg-phone" class="block text-xs font-semibold text-slate-500 mb-1.5">Phone Number (10 Digits)</label>
                    <input id="reg-phone" type="text" wire:model="phone" placeholder="9876543210" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                    @error('phone') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="reg-password" class="block text-xs font-semibold text-slate-500 mb-1.5">Password</label>
                    <input id="reg-password" type="password" wire:model="password" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition">
                    @error('password') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="sendOtp" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="sendOtp">Send Verification Code</span>
                    <span wire:loading wire:target="sendOtp" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sending Code...
                    </span>
                </button>
            </form>
        @else
            <!-- Step 2: Input Verification OTP -->
            <form wire:submit="verifyAndRegister" class="space-y-4">
                <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-xs text-indigo-700 leading-relaxed space-y-2">
                    <p>A verification code has been sent to your email address: <strong>{{ $email }}</strong>. Please enter the 6-digit code below to complete registration.</p>
                </div>

                <div>
                    <label for="reg-otp" class="block text-xs font-semibold text-slate-500 mb-1.5">One-Time Password (OTP)</label>
                    <input id="reg-otp" type="text" wire:model="enteredOtp" placeholder="123456" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition tracking-widest text-center text-lg font-bold">
                    @error('enteredOtp') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between text-xs">
                    <button type="button" wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp" class="font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="resendOtp">Resend Code</span>
                        <span wire:loading wire:target="resendOtp" class="flex items-center gap-1.5 text-indigo-500">
                            <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>
                    <button type="button" wire:click="$set('otpSent', false)" class="text-slate-500 hover:text-slate-600">Back to edit details</button>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="verifyAndRegister" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-650 py-3 text-sm font-bold text-white shadow hover:from-emerald-600 hover:to-teal-700 transition duration-300 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="verifyAndRegister">Verify & Create Account</span>
                    <span wire:loading wire:target="verifyAndRegister" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Verifying...
                    </span>
                </button>
            </form>
        @endif

        <div class="text-center border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-500 font-medium">
                Already have an account? 
                <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</div>
