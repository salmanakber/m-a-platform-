<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#faf9f7;color:#1c1917;font-family:Georgia,"Times New Roman",serif}
        .cv{max-width:820px;margin:32px auto;background:#fff;padding:52px 56px;border:1px solid #e7e5e4}
        .brand{font-family:"Helvetica Neue",Arial,sans-serif;font-size:10px;letter-spacing:.2em;text-transform:uppercase;color:#a8a29e;margin:0 0 18px}
        h1{margin:0;font-size:36px;font-weight:400;letter-spacing:-.03em;line-height:1.15}
        .role{margin:10px 0 0;font-family:"Helvetica Neue",Arial,sans-serif;font-size:14px;color:{{ $accent }}}
        .meta{margin:14px 0 0;font-family:"Helvetica Neue",Arial,sans-serif;font-size:12px;color:#78716c;line-height:1.6}
        .line{height:1px;background:#e7e5e4;margin:28px 0}
        h2{font-family:"Helvetica Neue",Arial,sans-serif;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:{{ $accent }};margin:0 0 12px;font-weight:600}
        p,li{font-size:15px;line-height:1.7}
        ul{margin:0 0 22px;padding-left:1.1rem}
        .two{display:grid;grid-template-columns:1fr 1fr;gap:28px}
        @media print{body{background:#fff}.cv{margin:0;border:0;padding:0}}
    </style>
</head>
<body>
<main class="cv">
    <p class="brand">Curriculum Vitae</p>
    <h1>{{ $payload['full_name'] ?? '' }}</h1>
    <p class="role">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
    <p class="meta">
        {{ $payload['location'] ?? '' }}
        @if(!empty($payload['email'])) · {{ $payload['email'] }}@endif
        @if(!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
        @if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
    </p>
    <div class="line"></div>
    @if(!empty($payload['summary']))
        <h2>Zusammenfassung</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
        <div class="line"></div>
    @endif
    @if(!empty($payload['services']))
        <h2>Leistungen</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
        <div class="line"></div>
    @endif
    <div class="two">
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
