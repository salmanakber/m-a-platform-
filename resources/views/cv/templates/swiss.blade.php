<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#0f172a;background:#f8fafc}
        .cv{max-width:820px;margin:28px auto;background:#fff;padding:42px 48px;border-top:6px solid {{ $accent }}}
        .row{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}
        h1{margin:0;font-size:30px;letter-spacing:-.03em;font-weight:650;color:#0f172a}
        .role{margin:6px 0 0;color:{{ $accent }};font-size:15px;font-weight:600}
        .meta{margin:10px 0 0;font-size:13px;color:#64748b;line-height:1.55;text-align:right;max-width:260px}
        .photo{width:84px;height:84px;object-fit:cover;border-radius:6px;margin-left:16px}
        h2{margin:28px 0 10px;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:{{ $accent }};font-weight:700}
        p,li{font-size:14px;line-height:1.6;color:#1e293b}
        ul{margin:0;padding-left:1.05rem}
        .cols{display:grid;grid-template-columns:1.2fr .8fr;gap:28px}
        @media print{body{background:#fff}.cv{margin:0;border-top-width:4px}}
    </style>
</head>
<body>
@php $photo = $payload['photo_path'] ?? null; @endphp
<main class="cv">
    <div class="row">
        <div>
            <h1>{{ $payload['full_name'] ?? '' }}</h1>
            <p class="role">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
        </div>
        <div style="display:flex;align-items:flex-start">
            <p class="meta">
                {{ $payload['location'] ?? '' }}<br>
                {{ $payload['email'] ?? '' }}<br>
                {{ $payload['phone'] ?? '' }}<br>
                {{ $payload['website'] ?? '' }}
                @if(!empty($payload['equity_capital']))<br>Eigenkapital: {{ $payload['equity_capital'] }}@endif
            </p>
            @if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
        </div>
    </div>
    @if(!empty($payload['summary']))
        <h2>Profil</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @endif
    <div class="cols">
        <div>
            @if(!empty($payload['services']))
                <h2>Leistungen</h2>
                <p>{!! nl2br(e($payload['services'])) !!}</p>
            @endif
            @foreach(['experience'=>'Berufserfahrung','experience_areas'=>'Schwerpunkte'] as $key=>$label)
                @if(!empty($payload[$key]) && is_array($payload[$key]))
                    <h2>{{ $label }}</h2>
                    <ul>@foreach($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
        <div>
            @foreach(['education'=>'Ausbildung','diplomas'=>'Diplome','skills'=>'Kompetenzen','languages'=>'Sprachen','criteria'=>'Kriterien'] as $key=>$label)
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
