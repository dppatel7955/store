<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm space-y-6">
            <h1 class="text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Payment Methods</h1>
            
            <div class="space-y-6 text-sm text-slate-650 leading-relaxed">
                <p>At Saffron Store, we support secure payment options to make buying high-performance hardware simple and safe. We offer the following options at checkout:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                    <!-- Method 1 -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2">
                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">Online Payments</span>
                        <h3 class="text-base font-bold text-slate-800">Razorpay Gateway</h3>
                        <p class="text-xs text-slate-500">Secure payments processed directly through Razorpay, supporting:</p>
                        <ul class="list-disc pl-5 text-xs text-slate-550 space-y-1 pt-1">
                            <li>UPI (Google Pay, PhonePe, Paytm, BHIM)</li>
                            <li>Credit & Debit Cards (Visa, Mastercard, RuPay)</li>
                            <li>NetBanking (major Indian banks)</li>
                            <li>Mobile Wallets</li>
                        </ul>
                    </div>

                    <!-- Method 2 -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-700/10">Pay on Delivery</span>
                        <h3 class="text-base font-bold text-slate-800">Cash on Delivery (COD)</h3>
                        <p class="text-xs text-slate-500">Pay physically with cash when the courier partner delivers the components directly to your doorstep. (Maximum order limits may apply).</p>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-slate-800 pt-4">Secure Transactions</h2>
                <p>All online transaction flows are secure and encrypted. Saffron Store does not store, see, or process your credit card numbers or banking secrets directly on our servers; everything is handled inside the Razorpay overlay window.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
