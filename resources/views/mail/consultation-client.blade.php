@extends('mail.layout')
@php($m = fn (string $k, array $r = []) => \App\Consultation\MailStrings::get($locale, $k, $r))
@section('content')
  <p style="margin:0 0 16px;font-size:17px;">{{ $m('greeting', ['name' => $booking->name]) }}</p>
  <p style="margin:0 0 28px;color:#3d3a35;">{{ $m('client_intro') }}</p>

  <div style="margin:0 0 24px;padding:18px 20px;background:#0b0b0c;border-radius:6px;text-align:center;">
    <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#a8a29a;">{{ $m('reference') }}</div>
    <div style="margin-top:6px;font-family:'Courier New',monospace;font-size:24px;letter-spacing:3px;color:#EBBD6C;" dir="ltr">{{ $booking->reference }}</div>
  </div>

  <h2 style="margin:0 0 12px;font-family:Georgia,serif;font-size:20px;font-weight:normal;color:#1b1b1d;">{{ $m('your_booking') }}</h2>
  @include('mail.partials.details', ['rows' => $details])

  <h2 style="margin:32px 0 12px;font-family:Georgia,serif;font-size:20px;font-weight:normal;color:#1b1b1d;">{{ $m('next_title') }}</h2>
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
    @foreach (['next_1', 'next_2', 'next_3'] as $i => $step)
    <tr><td style="padding:6px 0;vertical-align:top;width:34px;"><span style="display:inline-block;width:24px;height:24px;border-radius:12px;background:#EBBD6C;color:#0b0b0c;font-size:12px;font-weight:bold;line-height:24px;text-align:center;">{{ $i + 1 }}</span></td>
        <td style="padding:6px 0;color:#3d3a35;">{{ $m($step) }}</td></tr>
    @endforeach
  </table>
  <p style="margin:24px 0 0;font-size:13px;color:#6b6760;">{{ $m('keep_ref') }} {{ $m('reply_hint') }}</p>
@endsection
@section('footer')
  <div style="margin-top:6px;">{{ $m('footer_reason') }}</div>
@endsection
