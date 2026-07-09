<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verification Code</title>
</head>
<body style="font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 550px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin: 0 auto;">
        <!-- Header -->
        <tr>
            <td align="center" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 32px 24px; color: #ffffff;">
                <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">SAFFRON STORE</h1>
                <p style="margin: 8px 0 0 0; font-size: 11px; opacity: 0.8; font-weight: 600; letter-spacing: 1px;">SECURE ACCOUNT VERIFICATION</p>
            </td>
        </tr>
        <!-- Body -->
        <tr>
            <td style="padding: 40px 32px; color: #334155; line-height: 1.6;">
                <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #0f172a;">Verify Your Account</h2>
                <p style="margin: 0 0 24px 0; font-size: 13px; color: #475569;">Thank you for registering at Saffron Store. To complete your account verification and active your profile, please use the 6-digit verification code below:</p>
                
                <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin: 32px auto;">
                    <tr>
                        <td align="center" style="background-color: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px 36px; letter-spacing: 6px; font-size: 26px; font-weight: 800; color: #4f46e5; font-family: Courier, monospace;">
                            {{ $code }}
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 24px 0 0 0; font-size: 11px; color: #64748b;">This verification code is valid for 2 minutes. If you did not register for Saffron Store, please ignore this email.</p>
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td align="center" style="background-color: #f8fafc; border-top: 1px solid #f1f5f9; padding: 20px 32px; font-size: 10px; color: #94a3b8;">
                &copy; {{ date('Y') }} Saffron Store. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
