<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm space-y-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Shipping Policy</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: July 11, 2026</p>

            <div class="space-y-5 text-sm text-slate-600 leading-relaxed">
                <p>
                    This Shipping Policy explains how <strong>Saffron Store</strong> processes, ships, and delivers orders placed on our website.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">1. Order Processing</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Orders are typically processed within <strong>1–2 business days</strong> after payment confirmation (or successful COD placement).</li>
                    <li>Orders are not usually shipped on Sundays or public holidays.</li>
                    <li>During sale periods or high demand, processing may take longer. We will notify you by email if there is a significant delay.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">2. Shipping Charges</h2>
                <p>Shipping charges are calculated at checkout based on your order value:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Free shipping:</strong> available on eligible orders with a cart subtotal above <strong>₹50,000</strong>.</li>
                    <li><strong>Standard shipping:</strong> applied at checkout for orders below the free-shipping threshold (shown before you place the order).</li>
                </ul>
                <p class="text-xs text-slate-500">
                    Note: Coupons and discounts may affect the final payable amount; the free-shipping threshold is based on the cart subtotal before shipping.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">3. Delivery Estimates</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Standard delivery:</strong> generally <strong>3–7 business days</strong> after dispatch, depending on your location and courier partner.</li>
                    <li>Remote or restricted pin codes may take longer.</li>
                    <li>Delivery estimates are approximate and not guaranteed delivery dates.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">4. Shipping Address</h2>
                <p>
                    Please ensure your shipping name, phone number, and address are accurate at checkout. We are not responsible for delays or failed deliveries caused by incorrect or incomplete address details.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">5. Order Tracking</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>You can view your order status anytime from <a href="{{ route('orders') }}" class="text-indigo-600 font-semibold hover:underline">My Orders</a> after signing in.</li>
                    <li>Where available, tracking or dispatch updates may also be shared by email.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">6. Failed Delivery / Returns to Sender</h2>
                <p>
                    If a package is returned because the recipient was unavailable, the address was incorrect, or delivery was refused, we may contact you to arrange re-delivery. Additional shipping charges may apply for re-shipment.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">7. Damaged or Missing Items</h2>
                <p>
                    If your package arrives damaged or items are missing, please contact us within <strong>48 hours</strong> of delivery with your order ID and clear photos. We will investigate with the courier and arrange a suitable resolution as per our
                    <a href="{{ route('refund-policy') }}" class="text-indigo-600 font-semibold hover:underline">Refund &amp; Cancellation Policy</a>.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">8. Contact</h2>
                <p>
                    For shipping questions, email
                    <a href="mailto:support@saffronstore.local" class="text-indigo-600 font-semibold hover:underline">support@saffronstore.local</a>
                    with your order ID.
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
