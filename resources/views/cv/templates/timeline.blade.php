<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#0f172a;background:#eef2f6}
        .cv{max-width:840px;margin:24px auto;background:#fff;padding:40px 44px;box-shadow:0 10px 30px rgba(15,23,42,.08)}
        h1{margin:0;font-size:28px;color:{{ $accent }};letter-spacing:-.02em}
        .role{margin:6px 0 0;color:#64748b}
        .meta{margin:10px 0 0;font-size:13px;color:#64748b}
        h2{margin:26px 0 12px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:{{ $accent }}}
        p,li{font-size:14px;line-height:1.55}
        .tl{list-style:none;margin:0;padding:0 0 0 18px;border-left:2px solid {{ $accent }}}
        .tl li{position:relative;padding:0 0 16px 18px}
        .tl li::before{content:"";position:absolute;left:-23px;top:4px;width:10px;height:10px;border-radius:50%;background:{{ $accent }};border:2px solid #fff;box-shadow:0 0 0 2px {{ $accent }}}
        ul.plain{margin:0;padding-left:1.1rem}
        .side{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        @media print{body{background:#fff}.cv{margin:0;box-shadow:none}}
    </style>
</head>
<body>
<main class="cv">
    <h1>{{ $payload['full_name'] ?? '' }}</h1>
    <p class="role">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
    <p class="meta">
        {{ $payload['location'] ?? '' }}
        @if(!empty($payload['email'])) · {{ $payload['email'] }}@endif
        @if(!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
        @if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
    </p>
    @if(!empty($payload['summary']))
        <h2>Profil</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @endif
    @if(!empty($payload['experience']) && is_array($payload['experience']))
        <h2>Berufserfahrung</h2>
        <ul class="tl">@foreach($payload['experience'] as $item)<li>{{ $item }}</li>@endforeach</ul>
    @endif
    @if(!empty($payload['services']))
        <h2>Leistungen</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
    @endif
    <div class="side">
        @foreach(['education'=>'Ausbildung','skills'=>'Kompetenzen','experience_areas'=>'Schwerpunkte','languages'=>'Sprachen','diplomas'=>'Diplome'] as $key=>$label)
            @if(!empty($payload[$key]) && is_array($payload[$key]))
                <div>
                    <h2>{{ $label }}</h2>
                    <ul class="plain">@foreach($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                </div>
            @endif
        @endforeach
    </div>
</main>
</body>
</html>
