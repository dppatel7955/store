<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice for Order #{{ $order->id }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 24px 0;">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 32px; text-align: center; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">SAFFRON STORE</h1>
                            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.85; font-weight: 500;">Invoice for Order #{{ $order->id }}</p>
                        </td>
                    </tr>
                    
                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin-top: 0; margin-bottom: 24px; font-size: 14px; color: #334155; line-height: 1.6;">
                                Hi <strong>{{ $order->shipping_address['name'] ?? $order->user->name }}</strong>,<br><br>
                                Thank you for shopping with Saffron Store! Your order has been successfully placed. Below is the full breakdown of your invoice and billing details.
                            </p>
                            
                            <!-- Invoice Meta Information -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 28px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                                <tr>
                                    <td style="font-size: 12px; color: #64748b; line-height: 1.8;">
                                        <strong>Order Date:</strong> {{ $order->created_at->format('F d, Y h:i A') }}<br>
                                        <strong>Payment Method:</strong> <span style="text-transform: uppercase;">{{ $order->payment_method }}</span><br>
                                        <strong>Payment Status:</strong> <span style="text-transform: capitalize;">{{ $order->payment_status }}</span>
                                    </td>
                                    <td align="right" style="font-size: 12px; color: #64748b; line-height: 1.8; vertical-align: bottom;">
                                        <strong>Status:</strong> <span style="text-transform: uppercase; font-weight: bold; color: #4f46e5;">{{ $order->status }}</span>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Shipping Details -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 12px; font-weight: 800; color: #1e293b; text-transform: uppercase; tracking-wider: 0.5px;">Delivery Details</h3>
                                        <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.6;">
                                            <strong>{{ $order->shipping_address['name'] ?? '' }}</strong><br>
                                            {{ $order->shipping_address['street'] ?? '' }}<br>
                                            {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} - {{ $order->shipping_address['zip'] ?? '' }}<br>
                                            <strong>Phone:</strong> {{ $order->shipping_address['phone'] ?? '-' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Items List -->
                            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 12px; font-weight: 800; color: #1e293b; text-transform: uppercase;">Line Items</h3>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 24px; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                        <th style="padding: 8px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Product</th>
                                        <th style="padding: 8px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: center;">Qty</th>
                                        <th style="padding: 8px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: right;">Price</th>
                                        <th style="padding: 8px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 12px 0; font-size: 12px; color: #334155; font-weight: bold;">
                                                {{ $item->product->name ?? 'Product Item' }}
                                                <span style="display: block; font-size: 10px; color: #64748b; font-weight: normal; margin-top: 2px;">SKU: {{ $item->product->sku ?? '-' }}</span>
                                            </td>
                                            <td align="center" style="padding: 12px 0; font-size: 12px; color: #334155;">{{ $item->quantity }}</td>
                                            <td align="right" style="padding: 12px 0; font-size: 12px; color: #334155;">₹{{ number_format($item->unit_amount) }}</td>
                                            <td align="right" style="padding: 12px 0; font-size: 12px; color: #0f172a; font-weight: bold;">₹{{ number_format($item->total_amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            <!-- Financial Calculations -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td width="60%"></td>
                                    <td width="40%">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 12px; line-height: 2;">
                                            <tr>
                                                <td style="color: #64748b;">Subtotal:</td>
                                                <td align="right" style="color: #334155; font-weight: 500;">₹{{ number_format($order->grand_total - $order->shipping_amount) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b;">Shipping:</td>
                                                <td align="right" style="color: #334155; font-weight: 500;">₹{{ number_format($order->shipping_amount) }}</td>
                                            </tr>
                                            <tr style="font-size: 14px; font-weight: bold; border-top: 1px solid #e2e8f0;">
                                                <td style="color: #0f172a; padding-top: 8px;">Grand Total:</td>
                                                <td align="right" style="color: #4f46e5; padding-top: 8px; font-weight: 800;">₹{{ number_format($order->grand_total) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; line-height: 1.6;">
                            This is an automated transaction receipt. Please do not reply directly to this mail.<br>
                            If you have questions, contact us at <strong>support@saffronstore.com</strong>.<br><br>
                            &copy; {{ date('Y') }} Saffron Store. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
