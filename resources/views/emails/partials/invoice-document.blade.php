{{-- Professional invoice document block for email HTML --}}
@php
    $brandDeep = '#0E4971';
    $brand = '#009DE1';
    $ink = '#0a2a42';
    $stone = '#5a6b78';
    $statusColors = [
        'pending' => ['bg' => '#fff7e8', 'fg' => '#9a6700', 'label' => 'Offen'],
        'paid' => ['bg' => '#e8f8ef', 'fg' => '#0b6e4f', 'label' => 'Bezahlt'],
        'overdue' => ['bg' => '#fdecea', 'fg' => '#b42318', 'label' => 'Überfällig'],
        'cancelled' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'label' => 'Storniert'],
    ];
    $st = $statusColors[$paymentStatus] ?? $statusColors['pending'];
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:8px 0 20px;border:1px solid #c5d6e4;">
    {{-- Invoice header --}}
    <tr>
        <td style="padding:18px 18px 14px;background-color:#f4f8fc;border-bottom:1px solid #c5d6e4;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:top;">
                        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:{{ $brand }};">Rechnung</p>
                        <p style="margin:6px 0 0;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.2;color:{{ $brandDeep }};">{{ $invoiceNumber }}</p>
                    </td>
                    <td align="right" style="vertical-align:top;">
                        <span style="display:inline-block;padding:6px 10px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;background-color:{{ $st['bg'] }};color:{{ $st['fg'] }};">
                            {{ $st['label'] }}
                        </span>
                        <p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:{{ $stone }};">Datum {{ $invoiceDate }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Parties --}}
    <tr>
        <td style="padding:16px 18px;background-color:#ffffff;border-bottom:1px solid #e0ebf3;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td width="50%" style="vertical-align:top;padding-right:10px;">
                        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $stone }};">Rechnungssteller</p>
                        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:{{ $ink }};font-weight:700;">nachfolge-experten.ch</p>
                        <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.45;color:{{ $stone }};">
                            KMU Beratungen GmbH<br>
                            Brünigstrasse 144<br>
                            6060 Sarnen · Schweiz
                        </p>
                    </td>
                    <td width="50%" style="vertical-align:top;padding-left:10px;">
                        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $stone }};">Rechnungsempfänger</p>
                        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:{{ $ink }};font-weight:700;">{{ $companyName }}</p>
                        <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.45;color:{{ $stone }};">
                            {{ $contactName }}<br>
                            <a href="mailto:{{ $email }}" style="color:{{ $brand }};text-decoration:none;">{{ $email }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Line items --}}
    <tr>
        <td style="padding:0;background-color:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 18px;background-color:#0E4971;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#ffffff;width:58%;">Position</td>
                    <td style="padding:10px 18px;background-color:#0E4971;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#ffffff;width:18%;">Menge</td>
                    <td align="right" style="padding:10px 18px;background-color:#0E4971;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#ffffff;width:24%;">Betrag</td>
                </tr>
                <tr>
                    <td style="padding:14px 18px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.45;color:{{ $ink }};vertical-align:top;">
                        <strong>{{ $serviceTitle }}</strong>
                        @if (!empty($serviceDetail))
                            <br><span style="color:{{ $stone }};font-size:13px;">{{ $serviceDetail }}</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:{{ $ink }};vertical-align:top;">1 Monat</td>
                    <td align="right" style="padding:14px 18px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:{{ $ink }};font-weight:700;vertical-align:top;">{{ $amountFormatted }} {{ $currency }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Totals --}}
    <tr>
        <td style="padding:14px 18px 16px;background-color:#f7fafc;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{{ $stone }};">Zwischensumme</td>
                    <td align="right" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{{ $ink }};">{{ $amountFormatted }} {{ $currency }}</td>
                </tr>
                <tr>
                    <td style="padding-top:6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{{ $stone }};">MwSt.</td>
                    <td align="right" style="padding-top:6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{{ $ink }};">inkl. / gemäss Vereinbarung</td>
                </tr>
                <tr>
                    <td style="padding-top:12px;border-top:1px solid #c5d6e4;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:{{ $brandDeep }};">Total</td>
                    <td align="right" style="padding-top:12px;border-top:1px solid #c5d6e4;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:700;color:{{ $brandDeep }};">{{ $amountFormatted }} {{ $currency }}</td>
                </tr>
            </table>
        </td>
    </tr>

    @if (!empty($notes))
        <tr>
            <td style="padding:12px 18px 16px;background-color:#ffffff;border-top:1px solid #e0ebf3;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $stone }};">Bemerkung</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:{{ $ink }};">{{ $notes }}</p>
            </td>
        </tr>
    @endif

    @if (!empty($paymentHint))
        <tr>
            <td style="padding:14px 18px;background-color:#e5f5fd;border-top:1px solid #9fc5de;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:{{ $brand }};">Zahlungsinfo</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:{{ $ink }};">{{ $paymentHint }}</p>
            </td>
        </tr>
    @endif
</table>
