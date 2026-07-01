<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-16">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm space-y-6">
            <h1 class="text-3xl font-extrabold text-slate-900 border-b border-slate-200 pb-4">Privacy Policy</h1>
            <p class="text-xs text-slate-500 font-semibold">Last updated: {{ date('F d, Y') }}</p>
            
            <div class="space-y-4 text-sm text-slate-650 leading-relaxed">
                <p>Welcome to Saffron Store. We value your privacy and are committed to protecting your personal data. This privacy policy informs you about how we look after your personal data when you visit our website and tells you about your privacy rights.</p>
                
                <h2 class="text-lg font-bold text-slate-800 pt-4">1. Information We Collect</h2>
                <p>We collect and process personal information that you provide to us directly during account registration, checkout, and review submissions, including:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Identity Data (Name, username, or similar identifier).</li>
                    <li>Contact Data (Email address, phone number, and physical billing/shipping addresses).</li>
                    <li>Transaction Data (Details of products purchased and payments processed via Razorpay).</li>
                </ul>

                <h2 class="text-lg font-bold text-slate-800 pt-4">2. How We Use Your Information</h2>
                <p>We use the data we collect to process transactions, manage accounts, send transaction notifications and invoices, verify email access, and provide technical support.</p>

                <h2 class="text-lg font-bold text-slate-800 pt-4">3. Security</h2>
                <p>We implement industry-standard secure socket layers (SSL) and secure signature verifications to protect your transaction flows. All online payments are handled directly and securely through Razorpay's infrastructure; Saffron Store does not store your credit card or bank details on our servers.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
