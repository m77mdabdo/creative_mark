@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 16px;font-size:17px;">{{ $m('greeting', ['name' => $name]) }}</p>
  <p style="margin:0 0 20px;color:#3d3a35;">{{ $m('signin_intro') }}</p>
  <p style="margin:0 0 24px;text-align:center;" dir="ltr">
    <span style="display:inline-block;padding:16px 28px;background:#0b0b0c;color:#EBBD6C;font-family:'Courier New',monospace;font-size:32px;font-weight:bold;letter-spacing:10px;border-radius:6px;">{{ $code }}</span>
  </p>
  <p style="margin:0;font-size:13px;color:#6b6760;">{{ $m('signin_expires', ['minutes' => (string) $minutes]) }}</p>
@endsection
@section('footer')
  <div style="margin-top:6px;">{{ $m('signin_ignore') }}</div>
@endsection
