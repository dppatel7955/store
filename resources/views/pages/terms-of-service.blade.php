<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm space-y-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Terms of Service</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: July 11, 2026</p>

            <div class="space-y-5 text-sm text-slate-600 leading-relaxed">
                <p>
                    These Terms of Service (“Terms”) govern your access to and use of the <strong>Saffron Store</strong> website and services. By creating an account, browsing products, or placing an order, you agree to these Terms.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">1. Eligibility</h2>
                <p>
                    You must be at least 18 years old and capable of entering into a binding contract to use our store and place orders. You agree to provide accurate, current information during registration and checkout.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">2. Account &amp; Security</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>You are responsible for keeping your login credentials and OTP codes confidential.</li>
                    <li>You must not share verification codes with anyone.</li>
                    <li>We may suspend or terminate accounts involved in fraud, abuse, or policy violations.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">3. Products, Pricing &amp; Availability</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Product images, descriptions, and specifications are for guidance and may vary slightly.</li>
                    <li>Prices are listed in Indian Rupees (INR) and may change without prior notice.</li>
                    <li>Stock levels are updated regularly, but availability is not guaranteed until an order is confirmed.</li>
                    <li>We reserve the right to cancel or refuse orders in case of pricing errors, stock unavailability, suspected fraud, or payment failure.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">4. Orders &amp; Payments</h2>
                <p>
                    Orders are accepted subject to payment confirmation (for online payments) or successful placement (for Cash on Delivery, where available). Available payment methods are shown at checkout and on our
                    <a href="{{ route('payment-methods') }}" class="text-indigo-600 font-semibold hover:underline">Payment Methods</a> page.
                </p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Online payments are processed by third-party gateways (such as Razorpay).</li>
                    <li>By completing payment, you also agree to the gateway provider’s applicable terms.</li>
                    <li>An order confirmation / invoice email may be sent after successful placement.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">5. Shipping &amp; Delivery</h2>
                <p>
                    Delivery timelines, charges, and free-shipping thresholds are described in our
                    <a href="{{ route('shipping-policy') }}" class="text-indigo-600 font-semibold hover:underline">Shipping Policy</a>.
                    Risk of loss transfers to you upon delivery to the address provided at checkout.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">6. Cancellations, Returns &amp; Refunds</h2>
                <p>
                    Cancellation and refund rules are set out in our
                    <a href="{{ route('refund-policy') }}" class="text-indigo-600 font-semibold hover:underline">Refund &amp; Cancellation Policy</a>.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">7. Reviews &amp; User Content</h2>
                <p>
                    Product reviews must be honest and respectful. We may remove content that is abusive, false, spam, or violates law or these Terms.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">8. Coupons &amp; Offers</h2>
                <p>
                    Coupons and promotional offers are subject to their own validity dates, usage limits, and eligibility rules. Misuse of coupons may result in order cancellation.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">9. Intellectual Property</h2>
                <p>
                    All website content, branding, logos, product listings, and design elements of Saffron Store are owned by us or our licensors and may not be copied or reused without permission.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">10. Limitation of Liability</h2>
                <p>
                    To the fullest extent permitted by law, Saffron Store is not liable for indirect, incidental, or consequential damages arising from use of the website or purchase of products. Our total liability for any order is limited to the amount you paid for that order.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">11. Governing Law</h2>
                <p>
                    These Terms are governed by the laws of India. Disputes shall be subject to the exclusive jurisdiction of the competent courts in India, unless otherwise required by applicable consumer protection law.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">12. Contact</h2>
                <p>
                    Questions about these Terms can be sent to
                    <a href="mailto:support@saffronstore.local" class="text-indigo-600 font-semibold hover:underline">support@saffronstore.local</a>.
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
