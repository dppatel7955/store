<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm space-y-6">
            <h1 class="text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Shipping Policy</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: {{ date('F d, Y') }}</p>
            
            <div class="space-y-4 text-sm text-slate-650 leading-relaxed">
                <p>Thank you for shopping at Saffron Store. Below are the terms and conditions that constitute our Shipping Policy.</p>
                
                <h2 class="text-lg font-bold text-slate-800 pt-4">1. Shipment Processing Times</h2>
                <p>All orders are processed within 1-2 business days. Orders are not shipped or delivered on weekends or holidays. If we are experiencing a high volume of orders, shipments may be delayed by a few days. In the event of a significant delay, we will contact you via email.</p>

                <h2 class="text-lg font-bold text-slate-800 pt-4">2. Shipping Rates and Delivery Estimates</h2>
                <p>Shipping charges for your order will be calculated and displayed at checkout. We provide free standard shipping for all premium orders totaling above ₹5,000.</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Standard Delivery:</strong> 3-5 business days (Free for orders above ₹5,000, otherwise flat ₹150).</li>
                    <li><strong>Express Delivery:</strong> 1-2 business days (Flat ₹350).</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-4">3. Shipment Confirmation & Order Tracking</h2>
                <p>You will receive a shipment confirmation email once your order has shipped, containing your tracking number(s). You can track the status of your order anytime by visiting the "Track Order" link in our website footer or through your profile dashboard.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
