<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm space-y-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Refund &amp; Cancellation Policy</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: July 11, 2026</p>

            <div class="space-y-5 text-sm text-slate-600 leading-relaxed">
                <p>
                    This policy explains how cancellations, returns, and refunds work at <strong>Saffron Store</strong>. Please read it carefully before placing an order.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">1. Order Cancellation</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>You may request cancellation before the order is shipped / dispatched.</li>
                    <li>Once an order is packed or handed to the courier, cancellation may not be possible.</li>
                    <li>To request cancellation, contact us with your order ID from <a href="{{ route('orders') }}" class="text-indigo-600 font-semibold hover:underline">My Orders</a>.</li>
                    <li>We may cancel orders for payment failure, stock unavailability, pricing errors, or suspected fraud.</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">2. Returns</h2>
                <p>Returns are accepted only in the following cases (subject to verification):</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Item received is damaged, defective, or not as described</li>
                    <li>Wrong item delivered</li>
                    <li>Missing items from a multi-item order</li>
                </ul>
                <p class="pt-1">Return requests should generally be raised within <strong>7 days</strong> of delivery (or <strong>48 hours</strong> for visible transit damage).</p>
                <p>Products must be unused, in original packaging, with tags/accessories intact (unless the item is defective).</p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">3. Non-Returnable Items</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Items damaged due to misuse or improper handling after delivery</li>
                    <li>Products without original packaging / proof of purchase</li>
                    <li>Clearance / final-sale items marked as non-returnable (if applicable)</li>
                    <li>Digital products or gift cards (if offered in future)</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">4. Refunds</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Online payments:</strong> approved refunds are typically initiated within <strong>5–7 business days</strong> after inspection/approval. Credit to your bank/UPI/card may take additional time depending on your bank/payment provider.</li>
                    <li><strong>Cash on Delivery (COD):</strong> approved refunds may be issued via bank transfer / UPI to the account details you provide, or as store credit where offered.</li>
                    <li>Shipping charges are generally non-refundable unless the return is due to our error (wrong/damaged item).</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-2">5. Exchanges</h2>
                <p>
                    Where stock allows, we may offer a replacement for damaged or incorrect items instead of a refund. Size/color exchanges depend on product availability and variant stock.
                </p>

                <h2 class="text-lg font-bold text-slate-800 pt-2">6. How to Raise a Request</h2>
                <ol class="list-decimal pl-5 space-y-1.5">
                    <li>Sign in and open your order from My Orders</li>
                    <li>Email <a href="mailto:support@saffronstore.local" class="text-indigo-600 font-semibold hover:underline">support@saffronstore.local</a> with order ID, issue details, and photos (if applicable)</li>
                    <li>Our team will review and share next steps for pickup/return or refund</li>
                </ol>

                <h2 class="text-lg font-bold text-slate-800 pt-2">7. Contact</h2>
                <p>
                    For refund or cancellation help:
                    <a href="mailto:support@saffronstore.local" class="text-indigo-600 font-semibold hover:underline">support@saffronstore.local</a>
                </p>

                <p class="text-xs text-slate-400 pt-2">
                    Also see:
                    <a href="{{ route('shipping-policy') }}" class="text-indigo-600 hover:underline">Shipping Policy</a> ·
                    <a href="{{ route('terms-of-service') }}" class="text-indigo-600 hover:underline">Terms of Service</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
