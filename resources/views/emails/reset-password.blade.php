<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
</head>
<body style="font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 550px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin: 0 auto;">
        <!-- Header -->
        <tr>
            <td align="center" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 32px 24px; color: #ffffff;">
                <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">SAFFRON STORE</h1>
                <p style="margin: 8px 0 0 0; font-size: 11px; opacity: 0.8; font-weight: 600; letter-spacing: 1px;">SECURE PASSWORD RECOVERY</p>
            </td>
        </tr>
        <!-- Body -->
        <tr>
            <td style="padding: 40px 32px; color: #334155; line-height: 1.6;">
                <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #0f172a;">Hello {{ $name }},</h2>
                <p style="margin: 0 0 24px 0; font-size: 13px; color: #475569;">You are receiving this email because we received a password reset request for your account. Please click the button below to choose a new password:</p>
                
                <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin: 32px auto;">
                    <tr>
                        <td align="center">
                            <a href="{{ $resetUrl }}" style="background-color: #4f46e5; border-radius: 12px; padding: 14px 28px; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; display: inline-block; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);">
                                Reset Password
                            </a>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 24px 0 0 0; font-size: 11px; color: #64748b;">This password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p>
                <div style="border-top: 1px solid #e2e8f0; margin-top: 32px; padding-top: 20px; font-size: 11px; color: #94a3b8;">
                    If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
                    <a href="{{ $resetUrl }}" style="color: #4f46e5; word-break: break-all;">{{ $resetUrl }}</a>
                </div>
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
