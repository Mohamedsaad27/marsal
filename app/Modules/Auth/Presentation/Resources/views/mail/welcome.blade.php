@extends('auth::mail.layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:800;color:#0f172a;line-height:1.4;text-align:right;">
        {{ __('auth::messages.welcome_email_greeting', ['name' => $userName]) }}
    </h1>
    
    <p style="margin:0 0 24px;font-size:15px;color:#475569;line-height:1.6;text-align:right;">
        {{ __('auth::messages.welcome_email_intro') }}
    </p>

    @if(isset($isActive) && !$isActive)
        <div style="background-color:#fffbeb;border-right:4px solid #d97706;padding:16px;border-radius:8px;margin-bottom:24px;color:#b45309;font-size:13px;line-height:1.6;text-align:right;box-sizing:border-box;">
            <strong style="display:block;margin-bottom:4px;font-size:14px;">⚠️ حالة الحساب: قيد التفعيل</strong>
            حسابك غير نشط حالياً ويتطلب مراجعة أو تفعيل من قبل إدارة المنصة. يمكنك تسجيل الدخول بمجرد تفعيل الحساب.
        </div>
    @endif

    <!-- Credentials Box -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;margin:0 0 24px;width:100%;direction:rtl;">
        <!-- Header -->
        <tr>
            <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;background-color:#f1f5f9;border-top-left-radius:12px;border-top-right-radius:12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="direction:rtl;width:100%;">
                    <tr>
                        <td style="font-size:13px;font-weight:700;color:#334155;text-align:right;vertical-align:middle;">
                            <span style="display:inline-block;vertical-align:middle;margin-left:6px;font-size:14px;">🔒</span>
                            {{ __('auth::messages.welcome_email_subject_suffix') }}
                        </td>
                        @if(isset($isActive))
                            <td style="text-align:left;vertical-align:middle;">
                                @if($isActive)
                                    <span style="display:inline-block;background-color:#dcfce7;color:#15803d;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;line-height:1.5;">حساب نشط</span>
                                @else
                                    <span style="display:inline-block;background-color:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;line-height:1.5;">قيد التفعيل</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Fields -->
        <tr>
            <td style="padding:20px;text-align:right;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="direction:rtl;width:100%;">
                    <!-- Full Name -->
                    <tr>
                        <td style="padding:0 0 12px;vertical-align:top;width:35%;color:#64748b;font-size:13px;font-weight:600;">الاسم الكامل:</td>
                        <td style="padding:0 0 12px;vertical-align:top;color:#0f172a;font-size:14px;font-weight:700;">{{ $userName }}</td>
                    </tr>
                    <!-- Username (Email) -->
                    <tr>
                        <td style="padding:0 0 12px;vertical-align:top;width:35%;color:#64748b;font-size:13px;font-weight:600;">{{ __('auth::messages.email_label_email') }}:</td>
                        <td style="padding:0 0 12px;vertical-align:top;color:#0f172a;font-size:14px;font-weight:700;font-family:'Segoe UI', Tahoma, Arial, sans-serif;">{{ $email }}</td>
                    </tr>
                    <!-- Phone -->
                    @if(!empty($phone))
                        <tr>
                            <td style="padding:0 0 12px;vertical-align:top;width:35%;color:#64748b;font-size:13px;font-weight:600;">{{ __('auth::messages.email_label_phone') }}:</td>
                            <td style="padding:0 0 12px;vertical-align:top;color:#0f172a;font-size:14px;font-family:'Segoe UI', Tahoma, Arial, sans-serif;">{{ $phone }}</td>
                        </tr>
                    @endif
                    <!-- Roles -->
                    @if(!empty($roles))
                        <tr>
                            <td style="padding:0 0 12px;vertical-align:top;width:35%;color:#64748b;font-size:13px;font-weight:600;">الأدوار المعينة:</td>
                            <td style="padding:0 0 12px;vertical-align:top;color:#0f172a;font-size:14px;">
                                @php
                                    $roleLabels = [
                                        'super_admin' => 'مدير النظام',
                                        'staff_member' => 'موظف إداري',
                                        'shipping_company' => 'شركة شحن',
                                        'delivery_agent' => 'مندوب توصيل',
                                    ];
                                @endphp
                                @foreach($roles as $role)
                                    <span style="display:inline-block;background-color:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;margin-left:4px;margin-bottom:4px;white-space:nowrap;">
                                        {{ $roleLabels[$role] ?? $role }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @endif
                    <!-- Password -->
                    <tr>
                        <td style="padding:4px 0 0;vertical-align:middle;width:35%;color:#64748b;font-size:13px;font-weight:600;">{{ __('auth::messages.email_label_password') }}:</td>
                        <td style="padding:4px 0 0;vertical-align:middle;">
                            <code style="background-color:#ffffff;border:1px solid #cbd5e1;padding:6px 12px;border-radius:6px;font-size:14px;font-family:Consolas, Monaco, monospace;font-weight:700;color:#0f172a;letter-spacing:0.5px;display:inline-block;direction:ltr;">{{ $plainPassword }}</code>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Security Warning -->
    <div style="margin:0 0 28px;background-color:#fef2f2;border-right:4px solid #ef4444;padding:12px 16px;border-radius:8px;text-align:right;">
        <span style="font-size:13px;font-weight:600;color:#991b1b;line-height:1.5;">
            🛡️ {{ __('auth::messages.welcome_email_security_note') }}
        </span>
    </div>

    <!-- CTA Button -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 12px;width:100%;">
        <tr>
            <td align="center" style="text-align:center;">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $loginUrl }}" style="height:48px;v-text-anchor:middle;width:240px;" arcsize="17%" stroke="f" fillcolor="#2563eb">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:14px;font-weight:bold;">{{ __('auth::messages.welcome_email_cta') }}</center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-->
                <a href="{{ $loginUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:14px;box-shadow:0 4px 6px -1px rgba(37,99,235,0.2);text-align:center;line-height:1.2;transition:background-color 0.2s ease;">
                    {{ __('auth::messages.welcome_email_cta') }}
                </a>
                <!--<![endif]-->
            </td>
        </tr>
    </table>
@endsection
