<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#f4f1ea;color:#1c1917;font-family:"Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif}
        .wrap{max-width:860px;margin:28px auto;background:#fffdf8;padding:48px 52px;border:1px solid #e7e0d4}
        .brand{font-family:"Helvetica Neue",Arial,sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#78716c;margin:0 0 10px}
        h1{margin:0;font-size:34px;letter-spacing:-.03em;line-height:1.1}
        .sub{margin:8px 0 0;font-family:"Helvetica Neue",Arial,sans-serif;font-size:14px;color:#57534e}
        .rule{height:1px;background:#d6d3d1;margin:28px 0}
        h2{font-family:"Helvetica Neue",Arial,sans-serif;font-size:12px;letter-spacing:.14em;text-transform:uppercase;margin:0 0 10px;color:#0E4971}
        p,li{font-size:15px;line-height:1.65}
        ul{margin:0 0 18px;padding-left:1.15rem}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        @media print{body{background:#fff}.wrap{margin:0;border:0;padding:0}}
    </style>
</head>
<body>
<div class="wrap">
    <p class="brand">nachfolge-experten.ch · Curriculum Vitae</p>
    <h1>{{ $payload['full_name'] ?? '' }}</h1>
    <p class="sub">
        {{ $payload['headline'] ?? '' }}
        @if(!empty($payload['company'])) · {{ $payload['company'] }}@endif
        @if(!empty($payload['location'])) · {{ $payload['location'] }}@endif
    </p>
    <p class="sub">
        {{ $payload['email'] ?? '' }}
        @if(!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
        @if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
        @if(!empty($payload['equity_capital'])) · Eigenkapital {{ $payload['equity_capital'] }}@endif
    </p>
    <div class="rule"></div>
    @if (!empty($payload['summary']))
        <h2>Executive Summary</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
        <div class="rule"></div>
    @endif
    <div class="grid">
        <div>
            @foreach (['experience'=>'Berufliche Stationen','services'=>'Leistungsprofil'] as $key => $label)
                @if ($key === 'services' && !empty($payload['services']))
                    <h2>{{ $label }}</h2>
                    <p>{!! nl2br(e($payload['services'])) !!}</p>
                @elseif (!empty($payload[$key]) && is_array($payload[$key]))
                    <h2>{{ $label }}</h2>
                    <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
        <div>
            @foreach (['education'=>'Ausbildung','diplomas'=>'Diplome','experience_areas'=>'Schwerpunkte','skills'=>'Kompetenzen','languages'=>'Sprachen','criteria'=>'Kriterien'] as $key => $label)
                @if (!empty($payload[$key]) && is_array($payload[$key]))
                    <h2>{{ $label }}</h2>
                    <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
    </div>
</div>
</body>
</html>
