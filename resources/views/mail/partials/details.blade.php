{{-- Two-column detail table: $rows is a list of [label, value]. Values are escaped. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e2d6;border-radius:6px;border-collapse:separate;overflow:hidden;">
  @foreach ($rows as $i => [$label, $value])
  <tr>
    <td style="padding:12px 16px;background:{{ $i % 2 ? '#ffffff' : '#faf8f3' }};font-size:12px;color:#7A5C2C;width:38%;vertical-align:top;font-weight:bold;">{{ $label }}</td>
    <td style="padding:12px 16px;background:{{ $i % 2 ? '#ffffff' : '#faf8f3' }};font-size:14px;color:#1b1b1d;vertical-align:top;">{!! nl2br(e($value)) !!}</td>
  </tr>
  @endforeach
</table>
