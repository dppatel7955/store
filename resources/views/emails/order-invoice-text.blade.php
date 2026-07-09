SAFFRON STORE
Invoice for Order #{{ $order->id }}

Hi {{ $order->shipping_address['name'] ?? $order->user->name }},

Thank you for shopping with Saffron Store. Your order has been placed successfully.

Order Date: {{ $order->created_at->format('F d, Y h:i A') }}
Payment Method: {{ strtoupper($order->payment_method) }}
Payment Status: {{ $order->payment_status }}
Order Status: {{ $order->status }}
Grand Total: INR {{ number_format($order->grand_total, 2) }}

If you have questions, reply to this email.
