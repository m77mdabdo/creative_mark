@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 20px;font-size:17px;">{{ $m('staff_intro') }}</p>
  <div style="margin:0 0 24px;padding:16px 20px;background:#0b0b0c;border-radius:6px;">
    <span style="font-family:'Courier New',monospace;font-size:20px;letter-spacing:2px;color:#EBBD6C;" dir="ltr">{{ $booking->reference }}</span>
    <span style="color:#a8a29a;font-size:13px;"> · {{ $when }}</span>
  </div>

  <h2 style="margin:0 0 12px;font-family:Georgia,serif;font-size:20px;font-weight:normal;">{{ $m('client') }}</h2>
  @include('mail.partials.details', ['rows' => $clientRows])

  <h2 style="margin:28px 0 12px;font-family:Georgia,serif;font-size:20px;font-weight:normal;">{{ $m('your_booking') }}</h2>
  @include('mail.partials.details', ['rows' => $details])

  @if ($booking->message)
  <h2 style="margin:28px 0 12px;font-family:Georgia,serif;font-size:20px;font-weight:normal;">{{ $m('message') }}</h2>
  <div style="padding:16px 18px;border-inline-start:3px solid #EBBD6C;background:#faf8f3;color:#3d3a35;white-space:pre-line;" dir="auto">{{ $booking->message }}</div>
  @endif

  <p style="margin:32px 0 8px;text-align:center;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;padding:14px 28px;background:#EBBD6C;color:#0b0b0c;text-decoration:none;font-weight:bold;font-size:13px;letter-spacing:1px;text-transform:uppercase;border-radius:4px;">{{ $m('open_dashboard') }}</a>
  </p>
  <p style="margin:12px 0 0;font-size:12px;color:#6b6760;text-align:center;">{{ $m('staff_reply_hint') }}</p>
@endsection
