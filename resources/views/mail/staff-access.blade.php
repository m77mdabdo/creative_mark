@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 16px;font-size:17px;">{{ $m('greeting', ['name' => $name]) }}</p>
  <p style="margin:0 0 24px;color:#3d3a35;">{{ $intro }}</p>
  <p style="margin:0 0 24px;text-align:center;">
    <a href="{{ $url }}" style="display:inline-block;padding:14px 30px;background:#EBBD6C;color:#0b0b0c;text-decoration:none;font-weight:bold;font-size:14px;border-radius:4px;">{{ $button }}</a>
  </p>
  <p style="margin:0 0 8px;font-size:13px;color:#6b6760;">{{ $m('staff_expires', ['minutes' => (string) $minutes]) }}</p>
  <p style="margin:0;font-size:13px;color:#6b6760;">{{ $m('staff_account', ['email' => $email]) }}</p>
@endsection
@section('footer')
  <div style="margin-top:6px;">{{ $m('staff_ignore') }}</div>
@endsection
