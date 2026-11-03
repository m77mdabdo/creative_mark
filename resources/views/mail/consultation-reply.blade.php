@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 16px;font-size:17px;">{{ $m('greeting', ['name' => $booking->name]) }}</p>
  <div style="margin:0 0 28px;color:#1b1b1d;white-space:pre-line;" dir="auto">{{ $body }}</div>
  @if ($sender)
  <p style="margin:0 0 24px;color:#3d3a35;">{{ $sender }}<br><span style="color:#7A5C2C;">Creative Mark</span></p>
  @endif
  <div style="padding:12px 16px;background:#faf8f3;border-radius:6px;font-size:12px;color:#6b6760;">
    {{ $m('reference') }}: <span dir="ltr" style="font-family:'Courier New',monospace;color:#7A5C2C;">{{ $booking->reference }}</span> · {{ $m('reply_hint') }}
  </div>
@endsection
