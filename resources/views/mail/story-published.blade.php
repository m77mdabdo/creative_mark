@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 20px;font-size:17px;">{{ $m('story_intro') }}</p>
  <div style="margin:0 0 24px;padding:20px;border:1px solid #e7e2d6;border-radius:6px;background:#faf8f3;">
    <div style="font-family:Georgia,serif;font-size:21px;line-height:1.4;color:#1b1b1d;" dir="auto">{{ $headline }}</div>
    @if ($excerpt)
      <p style="margin:10px 0 0;color:#3d3a35;font-size:14px;" dir="auto">{{ $excerpt }}</p>
    @endif
    <p style="margin:12px 0 0;font-size:12px;color:#7A5C2C;font-weight:bold;">{{ $when }}</p>
  </div>
  <p style="margin:0;text-align:center;">
    <a href="{{ $siteUrl }}" style="display:inline-block;margin:4px;padding:13px 26px;background:#EBBD6C;color:#0b0b0c;text-decoration:none;font-weight:bold;font-size:13px;border-radius:4px;">{{ $m('story_view') }}</a>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;margin:4px;padding:13px 26px;background:#0b0b0c;color:#EBBD6C;text-decoration:none;font-weight:bold;font-size:13px;border-radius:4px;">{{ $m('open_dashboard') }}</a>
  </p>
@endsection
@section('footer')
  <div style="margin-top:6px;">{{ $m('story_reason') }}</div>
@endsection
