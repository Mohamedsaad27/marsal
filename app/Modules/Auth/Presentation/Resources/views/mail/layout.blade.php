<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? $appName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08);">
                <tr>
                    <td style="padding:32px 32px 16px;text-align:center;border-bottom:1px solid #eef2f7;">
                        <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="120" style="display:block;margin:0 auto;max-width:120px;height:auto;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;color:#1e293b;font-size:15px;line-height:1.7;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px 28px;text-align:center;background:#f8fafc;border-top:1px solid #eef2f7;color:#64748b;font-size:12px;line-height:1.5;">
                        {{ $appName }} &mdash; {{ __('auth::messages.email_footer') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
