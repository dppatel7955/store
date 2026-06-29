<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

    public function sendOtp()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric|digits:10|unique:users,phone',
            'password' => 'required|string|min:6',
        ]);

        // Generate OTP
        $this->generatedOtp = (string) rand(100000, 999999);
        $this->otpSent = true;

        // Log the OTP code for offline verification
        Log::info("OTP Code for customer registration (Phone: {$this->phone}): {$this->generatedOtp}");

        // Dispatch sweetalert based on environment debug mode
        if (config('app.debug')) {
            $this->dispatch('swal', 
                title: 'OTP Code Dispatched!', 
                text: 'Your registration verification code is: ' . $this->generatedOtp, 
                icon: 'info',
                toast: false
            );
        } else {
            $this->dispatch('swal', 
                title: 'OTP Dispatched!', 
                text: 'A 6-digit verification code has been sent to your phone number.', 
                icon: 'success',
                toast: true
            );
        }
    }

    public function verifyAndRegister()
    {
        $this->validate([
            'enteredOtp' => 'required|numeric|digits:6',
        ]);

        if ($this->enteredOtp !== $this->generatedOtp) {
            $this->addError('enteredOtp', 'The entered verification code is incorrect.');
            return;
        }

        // Create Customer User
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'is_admin' => false,
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

        Log::info("Resent OTP Code for customer registration (Phone: {$this->phone}): {$this->generatedOtp}");
        
        if (config('app.debug')) {
            $this->dispatch('swal', 
                title: 'OTP Resent!', 
                text: 'Your new verification code is: ' . $this->generatedOtp, 
                icon: 'info',
                toast: false
            );
        } else {
            $this->dispatch('swal', 
                title: 'OTP Resent!', 
                text: 'A new 6-digit verification code has been sent to your phone number.', 
                icon: 'success',
                toast: true
            );
        }
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

                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300">
                    Send Verification OTP
                </button>
            </form>
        @else
            <!-- Step 2: Input Verification OTP -->
            <form wire:submit="verifyAndRegister" class="space-y-4">
                <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-xs text-indigo-700 leading-relaxed space-y-2">
                    <p>A verification code has been dispatched to <strong>{{ $phone }}</strong>. Please enter the 6-digit OTP to complete registration.</p>
                    @if(config('app.debug'))
                        <p class="font-bold text-indigo-900 mt-2 bg-indigo-100/50 p-2 rounded-lg border border-indigo-200">
                            [Development Mode Helper] Active Verification OTP: <span class="tracking-widest text-sm font-extrabold select-all">{{ $generatedOtp }}</span>
                        </p>
                    @endif
                </div>

                <div>
                    <label for="reg-otp" class="block text-xs font-semibold text-slate-500 mb-1.5">One-Time Password (OTP)</label>
                    <input id="reg-otp" type="text" wire:model="enteredOtp" placeholder="123456" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition tracking-widest text-center text-lg font-bold">
                    @error('enteredOtp') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between text-xs">
                    <button type="button" wire:click="resendOtp" class="font-bold text-indigo-600 hover:text-indigo-700">Resend Code</button>
                    <button type="button" wire:click="$set('otpSent', false)" class="text-slate-500 hover:text-slate-600">Back to edit details</button>
                </div>

                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-650 py-3 text-sm font-bold text-white shadow hover:from-emerald-600 hover:to-teal-700 transition duration-300">
                    Verify & Create Account
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
