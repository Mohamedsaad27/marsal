<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? $appName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Global resets */
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            -webkit-text-size-adjust: 100% !important;
            -ms-text-size-adjust: 100% !important;
            -webkit-font-smoothing: antialiased !important;
        }
        table, td {
            border-collapse: collapse !important;
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }
        img {
            border: 0 !important;
            line-height: 100% !important;
            outline: none !important;
            text-decoration: none !important;
        }
        /* Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');
        
        body, table, td, p, a, span {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
        }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .content-padding {
                padding: 28px 20px !important;
            }
            .header-padding {
                padding: 24px 20px 20px !important;
            }
            .footer-padding {
                padding: 20px 20px 24px !important;
            }
            .mobile-stack {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .mobile-margin-bottom {
                margin-bottom: 12px !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc;width:100%;margin:0;padding:0;">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <!--[if mso]>
            <table role="presentation" width="580" style="width:580px;">
            <tr>
            <td>
            <![endif]-->
            <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 10px 15px -3px rgba(15,23,42,0.02), 0 4px 6px -4px rgba(15,23,42,0.02);">
                <!-- Header -->
                <tr>
                    <td class="header-padding" align="center" style="padding:40px 48px 24px;border-bottom:1px solid #f1f5f9;background-color:#ffffff;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td align="center">
                                    <!-- Styled Text Logo -->
                                    <div style="direction: rtl; text-align: center;">
                                        <div style="font-size: 26px; font-weight: 800; color: #1e3a8a; letter-spacing: -0.5px; line-height: 1.2;">
                                            مرسـال
                                        </div>
                                        <div style="font-size: 11px; font-weight: 700; color: #3b82f6; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; line-height: 1.2;">
                                            لخدمات الشحن والتوصيل
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Content -->
                <tr>
                    <td class="content-padding" style="padding:40px 48px;color:#334155;font-size:15px;line-height:1.75;text-align:right;direction:rtl;">
                        @yield('content')
                    </td>
                </tr>
                <!-- Footer -->
                <tr>
                    <td class="footer-padding" align="center" style="padding:24px 48px 32px;background:#f8fafc;border-top:1px solid #f1f5f9;color:#64748b;font-size:12px;line-height:1.6;direction:rtl;">
                        <p style="margin:0 0 6px;font-weight:600;color:#475569;">{{ $appName }}</p>
                        <p style="margin:0 0 16px;color:#94a3b8;">{{ __('auth::messages.email_footer') }}</p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                            <tr>
                                <td style="color:#cbd5e1;font-size:12px;">&copy; {{ date('Y') }} مرسال. جميع الحقوق محفوظة.</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
</body>
</html>
