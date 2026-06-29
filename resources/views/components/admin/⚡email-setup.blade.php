<?php

use Livewire\Component;

new class extends Component
{
    public string $host = '127.0.0.1';
    public string $port = '1025';
    public string $username = '';
    public string $password = '';
    public string $encryption = 'none';
    public string $from_address = 'hello@example.com';
    public string $from_name = 'Saffron Store';

    public function mount()
    {
        if (file_exists(storage_path('app/mail_setup.json'))) {
            $settings = json_decode(file_get_contents(storage_path('app/mail_setup.json')), true);
            if ($settings) {
                $this->host = $settings['host'] ?? '127.0.0.1';
                $this->port = $settings['port'] ?? '1025';
                $this->username = $settings['username'] ?? '';
                $this->password = $settings['password'] ?? '';
                $this->encryption = $settings['encryption'] ?? 'none';
                $this->from_address = $settings['from_address'] ?? 'hello@example.com';
                $this->from_name = $settings['from_name'] ?? 'Saffron Store';
            }
        }
    }

    public function save()
    {
        $this->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'encryption' => 'required|in:none,ssl,tls',
            'from_address' => 'required|email',
            'from_name' => 'required|string',
        ]);

        $settings = [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
        ];

        file_put_contents(storage_path('app/mail_setup.json'), json_encode($settings, JSON_PRETTY_PRINT));

        $this->dispatch('swal', title: 'Success!', text: 'Email settings saved successfully.', icon: 'success');
    }

    public function testConnection()
    {
        $this->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'from_address' => 'required|email',
            'from_name' => 'required|string',
        ]);

        try {
            // Apply config temporarily
            config([
                'mail.mailers.smtp.host' => $this->host,
                'mail.mailers.smtp.port' => $this->port,
                'mail.mailers.smtp.username' => $this->username ?: null,
                'mail.mailers.smtp.password' => $this->password ?: null,
                'mail.mailers.smtp.encryption' => $this->encryption === 'none' ? null : $this->encryption,
                'mail.from.address' => $this->from_address,
                'mail.from.name' => $this->from_name,
            ]);
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Saffron Store to verify SMTP mail configurations.', function ($message) {
                $message->to(auth()->user()->email)
                        ->subject('Saffron Store SMTP Connection Test');
            });

            $this->dispatch('swal', title: 'SMTP Test Successful!', text: 'Test email successfully sent to ' . auth()->user()->email, icon: 'success');
        } catch (\Exception $e) {
            $this->dispatch('swal', title: 'SMTP Connection Failed', text: 'Error Details: ' . $e->getMessage(), icon: 'error');
        }
    }
};
?>

<div class="space-y-6 max-w-2xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Email Setup</h1>
            <p class="text-xs text-slate-500 mt-1">Configure SMTP settings to automatically send order invoices to customers.</p>
        </div>
    </div>

    <!-- Setup Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Host -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">SMTP Host</label>
                    <input 
                        type="text" 
                        wire:model="host" 
                        placeholder="e.g. smtp.mailtrap.io" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition font-mono"
                    />
                    @error('host') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Port -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">SMTP Port</label>
                    <input 
                        type="text" 
                        wire:model="port" 
                        placeholder="e.g. 2525" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-655 focus:ring-1 focus:ring-indigo-600 transition font-mono"
                    />
                    @error('port') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Username</label>
                    <input 
                        type="text" 
                        wire:model="username" 
                        placeholder="SMTP Username" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('username') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Password</label>
                    <input 
                        type="password" 
                        wire:model="password" 
                        placeholder="SMTP Password" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('password') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Encryption -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Encryption</label>
                    <select 
                        wire:model="encryption" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-850 focus:outline-none focus:border-indigo-650"
                    >
                        <option value="none">None (Plain)</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                    @error('encryption') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Spacer for grid alignment -->
                <div class="hidden sm:block"></div>

                <!-- Sender Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Sender Email Address</label>
                    <input 
                        type="email" 
                        wire:model="from_address" 
                        placeholder="e.g. no-reply@saffronstore.com" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('from_address') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Sender Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Sender Name</label>
                    <input 
                        type="text" 
                        wire:model="from_name" 
                        placeholder="e.g. Saffron Store Support" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('from_name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Action buttons -->
            <div class="border-t border-slate-200 pt-5 flex items-center justify-end gap-3.5">
                <button 
                    type="button" 
                    wire:click="testConnection"
                    class="rounded-xl border border-slate-250 bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition inline-flex items-center gap-1.5 shadow-sm"
                >
                    <svg class="h-4 w-4 text-slate-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Test Email
                </button>

                <button 
                    type="submit"
                    class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-5 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
                >
                    Save Configuration
                </button>
            </div>
        </form>
    </div>
</div>
