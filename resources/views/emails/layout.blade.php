<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'nachfolge-experten.ch' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#e8f3fb;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
@php
    $appUrl = rtrim(config('app.url'), '/');
    $brandDeep = '#0E4971';
    $brand = '#009DE1';
    $brandIce = '#D0E9FF';
    $ink = '#0a2a42';
    $stone = '#5a6b78';
@endphp
@if (!empty($preheader))
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}
    </div>
@endif

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background-color:#e8f3fb;">
    <tr>
        <td align="center" style="padding:28px 16px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="border-collapse:collapse;width:100%;max-width:600px;">
                {{-- Brand header --}}
                <tr>
                    <td style="background:linear-gradient(155deg,#041929 0%,#0E4971 55%,#062338 100%);background-color:{{ $brandDeep }};padding:28px 32px 24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:{{ $brandIce }};">
                                        nachfolge-experten.ch
                                    </p>
                                    <p style="margin:8px 0 0;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.25;color:#ffffff;font-weight:normal;">
                                        {{ $headline ?? 'Schweizer M&amp;A-Verzeichnis' }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Accent line --}}
                <tr>
                    <td style="height:4px;line-height:4px;font-size:0;background-color:{{ $brand }};">&nbsp;</td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:32px 32px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:{{ $ink }};">
                        {!! $bodyHtml !!}

                        @if (!empty($ctaUrl) && !empty($ctaLabel))
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 8px;">
                                <tr>
                                    <td style="background-color:{{ $brandDeep }};">
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:14px 22px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                                            {{ $ctaLabel }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f4f8fc;border-top:1px solid #c5d6e4;padding:22px 32px;">
                        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;color:{{ $brandDeep }};">
                            nachfolge-experten.ch
                        </p>
                        <p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:{{ $stone }};">
                            Unabhängiges Schweizer Verzeichnis für M&amp;A- und Unternehmensnachfolge.<br>
                            KMU Beratungen GmbH · Brünigstrasse 144 · 6060 Sarnen
                        </p>
                        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;">
                            <a href="{{ $appUrl }}" style="color:{{ $brand }};text-decoration:none;font-weight:700;">Zur Website</a>
                            &nbsp;·&nbsp;
                            <a href="{{ $appUrl }}/impressum" style="color:{{ $stone }};text-decoration:none;">Impressum</a>
                            &nbsp;·&nbsp;
                            <a href="{{ $appUrl }}/datenschutz" style="color:{{ $stone }};text-decoration:none;">Datenschutz</a>
                        </p>
                    </td>
                </tr>
            </table>

            <p style="margin:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.45;color:#8494a0;text-align:center;">
                Diese E-Mail wurde automatisch von nachfolge-experten.ch versendet.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
