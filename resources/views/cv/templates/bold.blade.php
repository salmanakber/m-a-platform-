<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#111827;background:#f1f5f9}
        .cv{max-width:860px;margin:20px auto;background:#fff;overflow:hidden;box-shadow:0 12px 36px rgba(15,23,42,.1)}
        .head{background:{{ $accent }};color:#fff;padding:36px 40px;display:flex;gap:22px;align-items:center}
        .head img{width:92px;height:92px;object-fit:cover;border-radius:8px;border:3px solid rgba(255,255,255,.35)}
        h1{margin:0;font-size:32px;letter-spacing:-.03em}
        .role{margin:6px 0 0;opacity:.9;font-size:15px}
        .meta{margin:10px 0 0;font-size:13px;opacity:.85;line-height:1.5}
        .body{padding:28px 40px 40px}
        h2{margin:20px 0 8px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:{{ $accent }}}
        p,li{font-size:14px;line-height:1.55}
        ul{margin:0;padding-left:1.1rem}
        .grid{display:grid;grid-template-columns:1.15fr .85fr;gap:24px}
        @media print{body{background:#fff}.cv{margin:0;box-shadow:none}}
    </style>
</head>
<body>
@php $photo = $payload['photo_path'] ?? null; @endphp
<div class="cv">
    <header class="head">
        @if($photo)<img src="{{ asset('storage/'.$photo) }}" alt="">@endif
        <div>
            <h1>{{ $payload['full_name'] ?? '' }}</h1>
            <p class="role">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
            <p class="meta">
                {{ $payload['location'] ?? '' }}
                @if(!empty($payload['email'])) · {{ $payload['email'] }}@endif
                @if(!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
                @if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
            </p>
        </div>
    </header>
    <div class="body">
        @if(!empty($payload['summary']))
            <h2>Profil</h2>
            <p>{!! nl2br(e($payload['summary'])) !!}</p>
        @endif
        <div class="grid">
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
                @foreach(['education'=>'Ausbildung','skills'=>'Kompetenzen','languages'=>'Sprachen','diplomas'=>'Diplome','criteria'=>'Kriterien'] as $key=>$label)
                    @if(!empty($payload[$key]) && is_array($payload[$key]))
                        <h2>{{ $label }}</h2>
                        <ul>@foreach($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
</body>
</html>
