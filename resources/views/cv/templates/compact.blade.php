<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#111827;background:#fff;font-size:12.5px}
        .cv{max-width:780px;margin:0 auto;padding:22px 26px}
        header{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid {{ $accent }};padding-bottom:10px;margin-bottom:12px}
        h1{margin:0;font-size:22px;color:{{ $accent }};letter-spacing:-.02em}
        .sub{margin:2px 0 0;color:#4b5563}
        .meta{text-align:right;color:#6b7280;line-height:1.4}
        h2{margin:12px 0 4px;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:{{ $accent }}}
        p,li{margin:0 0 4px;line-height:1.45}
        ul{margin:0;padding-left:1rem}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 18px}
        @media print{.cv{padding:0}}
    </style>
</head>
<body>
<main class="cv">
    <header>
        <div>
            <h1>{{ $payload['full_name'] ?? '' }}</h1>
            <p class="sub">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
        </div>
        <p class="meta">
            {{ $payload['location'] ?? '' }} · {{ $payload['email'] ?? '' }}<br>
            {{ $payload['phone'] ?? '' }}@if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
            @if(!empty($payload['equity_capital']))<br>EK {{ $payload['equity_capital'] }}@endif
        </p>
    </header>
    @if(!empty($payload['summary']))
        <h2>Profil</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @endif
    @if(!empty($payload['services']))
        <h2>Leistungen</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
    @endif
    <div class="grid">
        <div>
            @foreach(['experience'=>'Erfahrung','experience_areas'=>'Schwerpunkte'] as $key=>$label)
                @if(!empty($payload[$key]) && is_array($payload[$key]))
                    <h2>{{ $label }}</h2>
                    <ul>@foreach($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
        <div>
            @foreach(['education'=>'Ausbildung','skills'=>'Kompetenzen','languages'=>'Sprachen','diplomas'=>'Diplome'] as $key=>$label)
                @if(!empty($payload[$key]) && is_array($payload[$key]))
                    <h2>{{ $label }}</h2>
                    <ul>@foreach($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
    </div>
</main>
</body>
</html>
