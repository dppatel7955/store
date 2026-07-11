<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm space-y-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Payment Methods</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: July 11, 2026</p>

            <div class="space-y-6 text-sm text-slate-600 leading-relaxed">
                <p>
                    At <strong>Saffron Store</strong>, available payment options are managed from the admin panel and shown live at checkout. Currently active methods:
                </p>

                @if(($paymentMethods ?? collect())->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($paymentMethods as $method)
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset {{ $method->handler === 'razorpay' ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10' : 'bg-emerald-50 text-emerald-700 ring-emerald-700/10' }}">
                                    {{ strtoupper($method->handler) }}
                                </span>
                                <h2 class="text-base font-bold text-slate-800">{{ $method->name }}</h2>
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

                <h2 class="text-lg font-bold text-slate-800 pt-2">Secure Transactions</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Online payments are processed through encrypted third-party gateways.</li>
                    <li>We do not store your full card number, CVV, UPI PIN, or net-banking passwords.</li>
                    <li>Payment confirmation is required before online orders are fulfilled.</li>
                    <li>For Cash on Delivery (when enabled), please keep the exact payable amount ready if requested by the courier.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">Failed Payments</h2>
                <p>
                    If an online payment fails or is pending, the order may not be confirmed. Any amount deducted but not confirmed is usually auto-reversed by the payment provider within a few business days. Contact support with your payment reference if needed.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">Refunds</h2>
                <p>
                    Refund timelines and eligibility are covered in our
                    <a href="{{ route('refund-policy') }}" class="text-indigo-600 font-semibold hover:underline">Refund &amp; Cancellation Policy</a>.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">Need Help?</h2>
                <p>
                    Email
                    <a href="mailto:support@saffronstore.local" class="text-indigo-600 font-semibold hover:underline">support@saffronstore.local</a>
                    with your order ID or payment reference.
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
