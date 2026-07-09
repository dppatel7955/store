<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }} - Saffron Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans p-6 sm:p-12">
    <!-- Print toolbar -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('admin.orders.detail', ['id' => $order->id]) }}" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
            &larr; Back to Order Details
        </a>
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-xl shadow-sm transition">
            Print Document
        </button>
    </div>

    <!-- Invoice Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">SAFFRON STORE</h1>
                <p class="text-xs text-slate-500 mt-1.5">Premium Multi-Category E-Commerce Outlet</p>
                <p class="text-xs text-slate-450 mt-1">saffronstore@gmail.com</p>
            </div>
            <div class="sm:text-right">
                <h2 class="text-xl font-bold text-indigo-600">INVOICE</h2>
                <p class="text-xs font-mono font-bold text-slate-700 mt-1">#INV-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p class="text-xs text-slate-450 mt-1">Date: {{ $order->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        <!-- Address Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8">
            <div>
                <h3 class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2">Billed & Shipped To</h3>
                @php $addr = $order->shipping_address; @endphp
                <p class="text-xs text-slate-800 font-bold">{{ $addr['name'] ?? '' }}</p>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    {{ $addr['street'] ?? '' }}<br>
                    {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} - {{ $addr['zip'] ?? '' }}
                </p>
                <p class="text-xs font-mono text-slate-500 mt-1.5">Phone: {{ $addr['phone'] ?? '-' }}</p>
            </div>
            <div class="sm:text-right">
                <h3 class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2">Payment Info</h3>
                <p class="text-xs text-slate-800"><span class="font-semibold text-slate-450">Method:</span> <span class="font-bold">{{ $order->paymentMethodConfig->name ?? strtoupper($order->payment_method) }}</span></p>
                <p class="text-xs text-slate-800 mt-1"><span class="font-semibold text-slate-450">Status:</span> <span class="capitalize font-bold">{{ $order->payment_status }}</span></p>
                <p class="text-xs text-slate-850 mt-1.5"><span class="font-semibold text-slate-450">Order ID:</span> #{{ $order->id }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-left border-collapse mb-8">
            <thead>
                <tr class="border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                    <th class="pb-3">Product Name / SKU</th>
                    <th class="pb-3 text-right">Unit Price</th>
                    <th class="pb-3 text-center">Quantity</th>
                    <th class="pb-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60 text-xs">
                @foreach($order->items as $item)
                    <tr>
                        <td class="py-4">
                            <span class="font-bold text-slate-800">{{ $item->product->name ?? 'Deleted Item' }}</span>
                            <span class="block text-[10px] text-slate-450 font-mono mt-0.5">SKU: {{ $item->product->sku ?? '-' }}</span>
                        </td>
                        <td class="py-4 text-right text-slate-650">₹{{ number_format($item->unit_amount) }}</td>
                        <td class="py-4 text-center text-slate-700 font-medium">{{ $item->quantity }}</td>
                        <td class="py-4 text-right font-bold text-slate-900">₹{{ number_format($item->total_amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Calculation Details -->
        <div class="flex flex-col items-end gap-2 text-xs text-slate-600 border-t border-slate-200 pt-6 mb-12">
            <div class="flex justify-between w-64">
                <span>Subtotal:</span>
                <span class="font-semibold text-slate-800">₹{{ number_format($order->grand_total - $order->shipping_amount) }}</span>
            </div>
            <div class="flex justify-between w-64">
                <span>Shipping:</span>
                <span class="font-semibold text-slate-800">₹{{ number_format($order->shipping_amount) }}</span>
            </div>
            <div class="flex justify-between w-64 border-t border-slate-200 pt-2 text-sm font-black text-slate-900">
                <span>Total Due:</span>
                <span class="text-indigo-600 font-extrabold text-base">₹{{ number_format($order->grand_total) }}</span>
            </div>
        </div>

        <!-- Note & Sign-Off -->
        <div class="border-t border-slate-100 pt-6 text-center text-[10px] text-slate-400 leading-relaxed">
            Thank you for your business. For any invoice disputes, please reach out to billing@saffronstore.com.<br>
            Saffron Store &copy; {{ date('Y') }}.
        </div>
    </div>

    <!-- Auto Print Script -->
    <script>
        window.addEventListener('load', () => {
            // Slight delay to allow layouts to render completely before printing
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
