<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm space-y-6">
            <h1 class="text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Payment Methods</h1>
            
            <div class="space-y-6 text-sm text-slate-650 leading-relaxed">
                <p>At Saffron Store, payment options are managed in real time from our admin panel. The currently active checkout methods are listed below:</p>

                @if(($paymentMethods ?? collect())->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        @foreach($paymentMethods as $method)
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset {{ $method->handler === 'razorpay' ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10' : 'bg-emerald-50 text-emerald-700 ring-emerald-700/10' }}">
                                    {{ strtoupper($method->handler) }}
                                </span>
                                <h3 class="text-base font-bold text-slate-800">{{ $method->name }}</h3>
                                @if($method->description)
                                    <p class="text-xs text-slate-500">{{ $method->description }}</p>
                                @endif
                                @if($method->instructions)
                                    <p class="text-xs text-indigo-700 font-medium">{{ $method->instructions }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-xs font-semibold text-amber-700">
                        No payment methods are active right now. Please contact support.
                    </div>
                @endif

                <h2 class="text-lg font-bold text-slate-800 pt-4">Secure Transactions</h2>
                <p>All online transaction flows are secure and encrypted. Sensitive card and banking details are handled by the configured payment gateway provider.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
