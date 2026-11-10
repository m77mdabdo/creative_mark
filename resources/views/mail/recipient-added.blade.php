@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 16px;font-size:17px;">{{ $m('greeting', ['name' => $name]) }}</p>
  <p style="margin:0 0 16px;color:#3d3a35;">{{ $m('recipient_intro', ['by' => $by]) }}</p>
  <p style="margin:0 0 16px;color:#3d3a35;">{{ $m('recipient_what') }}</p>
  <p style="margin:0;font-size:13px;color:#6b6760;">{{ $m('recipient_address', ['email' => $email]) }}</p>
@endsection
@section('footer')
  <div style="margin-top:6px;">{{ $m('recipient_ignore') }}</div>
@endsection
