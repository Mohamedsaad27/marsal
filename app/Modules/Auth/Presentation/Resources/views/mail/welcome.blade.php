@extends('auth::mail.layout')

@section('content')
    <p style="margin:0 0 16px;font-size:18px;font-weight:600;color:#0f172a;">{{ __('auth::messages.welcome_email_greeting', ['name' => $userName]) }}</p>
    <p style="margin:0 0 20px;">{{ __('auth::messages.welcome_email_intro') }}</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:24px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 10px;"><strong>{{ __('auth::messages.email_label_login') }}:</strong> {{ $loginUrl }}</p>
                <p style="margin:0 0 10px;"><strong>{{ __('auth::messages.email_label_email') }}:</strong> {{ $email }}</p>
                <p style="margin:0 0 10px;"><strong>{{ __('auth::messages.email_label_phone') }}:</strong> {{ $phone }}</p>
                <p style="margin:0;"><strong>{{ __('auth::messages.email_label_password') }}:</strong>
                    <code style="background:#fff;padding:4px 10px;border-radius:4px;border:1px solid #e2e8f0;font-size:14px;letter-spacing:0.5px;">{{ $plainPassword }}</code>
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;color:#b45309;font-size:13px;">{{ __('auth::messages.welcome_email_security_note') }}</p>
    <p style="margin:0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;">{{ __('auth::messages.welcome_email_cta') }}</a>
    </p>
@endsection
