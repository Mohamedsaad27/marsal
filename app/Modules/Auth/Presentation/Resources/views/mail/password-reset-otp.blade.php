@extends('auth::mail.layout')

@section('content')
    <p style="margin:0 0 16px;font-size:18px;font-weight:600;color:#0f172a;">{{ __('auth::messages.reset_email_greeting', ['name' => $userName]) }}</p>
    <p style="margin:0 0 24px;">{{ __('auth::messages.reset_email_intro') }}</p>

    <div style="text-align:center;margin:0 0 24px;">
        <span style="display:inline-block;background:#f1f5f9;border:2px dashed #cbd5e1;border-radius:10px;padding:16px 32px;font-size:32px;font-weight:700;letter-spacing:8px;color:#0f172a;">{{ $otp }}</span>
    </div>

    <p style="margin:0 0 8px;color:#64748b;font-size:13px;">{{ __('auth::messages.reset_email_expiry', ['minutes' => $expiryMinutes]) }}</p>
    <p style="margin:0;color:#64748b;font-size:13px;">{{ __('auth::messages.reset_email_ignore') }}</p>
@endsection
