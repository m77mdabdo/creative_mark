@php($rtl = in_array($locale, ['ar', 'ur'], true))
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#efece4;font-family:{{ $rtl ? "Tahoma, 'Segoe UI', Arial, sans-serif" : "'Helvetica Neue', Helvetica, Arial, sans-serif" }};color:#1b1b1d;">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader ?? '' }}</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#efece4;">
  <tr><td align="center" style="padding:32px 12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:6px;overflow:hidden;" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
      <tr><td style="background:#0b0b0c;padding:28px 36px;text-align:{{ $rtl ? 'right' : 'left' }};">
        <div style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:1;color:#EBBD6C;letter-spacing:.5px;">Creative Mark</div>
        <div style="margin-top:8px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#a8a29a;">{{ $kicker ?? '' }}</div>
      </td></tr>
      <tr><td style="height:3px;background:#EBBD6C;line-height:3px;font-size:0;">&nbsp;</td></tr>
      <tr><td style="padding:36px;text-align:{{ $rtl ? 'right' : 'left' }};font-size:15px;line-height:1.7;">
        @yield('content')
      </td></tr>
      <tr><td style="background:#f7f5ef;padding:22px 36px;font-size:12px;line-height:1.6;color:#6b6760;text-align:{{ $rtl ? 'right' : 'left' }};border-top:1px solid #e7e2d6;">
        <div style="color:#7A5C2C;font-weight:bold;">Creative Mark · {{ \App\Consultation\MailStrings::get($locale, 'footer_offices') }}</div>
        @hasSection('footer')@yield('footer')@endif
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
