<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
      public string $token = '';
      public string $email = '';
      public string $password = '';
      public string $password_confirmation = '';

      public function mount(string $token = '')
      {
          $this->token = $token;
          $this->email = request()->query('email', '');
      }

      public function resetPassword()
      {
          $this->validate([
              'email' => 'required|email',
              'password' => 'required|min:8|confirmed',
          ]);

          $response = Password::broker()->reset(
              [
                  'token' => $this->token,
                  'email' => $this->email,
                  'password' => $this->password,
                  'password_confirmation' => $this->password_confirmation,
              ],
              function (User $user, string $password) {
                  $user->password = Hash::make($password);
                  $user->save();
                  
                  Auth::login($user);
              }
          );

          if ($response === Password::PASSWORD_RESET) {
              session()->flash('success', 'Your password has been successfully reset! You are now logged in.');
              return redirect()->intended('/shop');
          }

          $this->addError('email', __($response));
      }
};
?>

<div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="text-center">
            <h1 class="text-2xl font-extrabold text-slate-900">Reset Password</h1>
            <p class="text-xs text-slate-500 mt-1">Please enter your email and choose a secure new password.</p>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="resetPassword" class="space-y-4">
            <input type="hidden" wire:model="token">

            <div>
                <label for="reset_email" class="block text-xs font-semibold text-slate-550 mb-1.5">Email Address</label>
                <input type="email" id="reset_email" wire:model="email" placeholder="you@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                @error('email') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="reset_password" class="block text-xs font-semibold text-slate-550 mb-1.5">New Password</label>
                <input type="password" id="reset_password" wire:model="password" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                @error('password') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="reset_password_confirmation" class="block text-xs font-semibold text-slate-550 mb-1.5">Confirm New Password</label>
                <input type="password" id="reset_password_confirmation" wire:model="password_confirmation" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </div>

            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-3 text-sm font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition duration-300 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="resetPassword">Save New Password</span>
                <span wire:loading wire:target="resetPassword" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating Password...
                </span>
            </button>
        </form>
    </div>
</div>
