<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        @page { margin: 18mm 16mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #12202b;
            background: #fff;
        }
        .head { border-bottom: 2.5pt solid {{ $accent }}; padding-bottom: 10pt; margin-bottom: 14pt; }
        .name { margin: 0; font-size: 20pt; color: {{ $accent }}; letter-spacing: -0.3pt; }
        .role { margin: 4pt 0 0; font-size: 11pt; color: #4a5b68; }
        .meta { margin: 6pt 0 0; font-size: 9pt; color: #5a6b78; }
        h2 {
            margin: 13pt 0 5pt;
            font-size: 9.5pt;
            letter-spacing: 1pt;
            text-transform: uppercase;
            color: {{ $accent }};
            border-bottom: 0.6pt solid #d7e3ec;
            padding-bottom: 3pt;
        }
        p { margin: 0 0 7pt; }
        ul { margin: 0 0 8pt; padding-left: 14pt; }
        li { margin: 0 0 3pt; }
        .two { width: 100%; }
        .two td { vertical-align: top; width: 50%; padding-right: 10pt; }
        .two td + td { padding-right: 0; padding-left: 10pt; }
    </style>
</head>
<body>
    <div class="head">
        <h1 class="name">{{ $payload['full_name'] ?? '' }}</h1>
        <p class="role">
            {{ $payload['headline'] ?? '' }}
            @if (!empty($payload['company'])) · {{ $payload['company'] }}@endif
        </p>
        <p class="meta">
            {{ $payload['location'] ?? '' }}
            @if (!empty($payload['email'])) · {{ $payload['email'] }}@endif
            @if (!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
            @if (!empty($payload['website'])) · {{ $payload['website'] }}@endif
            @if (!empty($payload['equity_capital'])) · Eigenkapital: {{ $payload['equity_capital'] }}@endif
        </p>
    </div>

    @if (!empty($payload['summary']))
        <h2>Profil</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @endif

    @if (!empty($payload['services']))
        <h2>Leistungen</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
    @endif

    @if (!empty($payload['experience']) && is_array($payload['experience']))
        <h2>Berufserfahrung</h2>
        <ul>@foreach ($payload['experience'] as $item)<li>{{ $item }}</li>@endforeach</ul>
    @endif

    <table class="two" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                @foreach (['experience_areas' => 'Schwerpunkte', 'education' => 'Ausbildung'] as $key => $label)
                    @if (!empty($payload[$key]) && is_array($payload[$key]))
                        <h2>{{ $label }}</h2>
                        <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @endif
                @endforeach
            </td>
            <td>
                @foreach (['skills' => 'Kompetenzen', 'languages' => 'Sprachen', 'diplomas' => 'Diplome', 'criteria' => 'Kriterien'] as $key => $label)
                    @if (!empty($payload[$key]) && is_array($payload[$key]))
                        <h2>{{ $label }}</h2>
                        <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @endif
                @endforeach
            </td>
        </tr>
    </table>
</body>
</html>
