<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; font-family: Georgia, "Times New Roman", serif; color:#12202b; background:#eef3f7; }
        .sheet { max-width:900px; margin:24px auto; background:#fff; display:grid; grid-template-columns:280px 1fr; min-height:1000px; box-shadow:0 12px 40px rgba(14,73,113,.12); }
        .side { background:#0E4971; color:#e8f2f8; padding:36px 28px; }
        .side h1 { font-family:"Helvetica Neue", Helvetica, Arial, sans-serif; font-size:26px; margin:0 0 6px; letter-spacing:-0.02em; }
        .side .role { opacity:.85; margin:0 0 18px; font-family:"Helvetica Neue", Helvetica, Arial, sans-serif; font-size:13px; }
        .side img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:10px; margin-bottom:18px; }
        .side h2, .main h2 { font-family:"Helvetica Neue", Helvetica, Arial, sans-serif; font-size:12px; letter-spacing:.12em; text-transform:uppercase; margin:22px 0 8px; }
        .side h2 { color:#9fd3f0; border:0; }
        .side p, .side li { font-family:"Helvetica Neue", Helvetica, Arial, sans-serif; font-size:13px; line-height:1.5; }
        .side ul { margin:0; padding-left:1rem; }
        .main { padding:40px 36px; }
        .main h2 { color:#0E4971; border-bottom:1px solid #d7e3ec; padding-bottom:6px; }
        .main p, .main li { font-size:15px; line-height:1.6; }
        .main ul { margin:0; padding-left:1.1rem; }
        @media print { body { background:#fff; } .sheet { margin:0; box-shadow:none; } }
        @media (max-width:720px) { .sheet { grid-template-columns:1fr; } }
    </style>
</head>
<body>
@php $photo = $payload['photo_path'] ?? null; @endphp
<div class="sheet">
    <aside class="side">
        @if ($photo)<img src="{{ asset('storage/'.$photo) }}" alt="">@endif
        <h1>{{ $payload['full_name'] ?? '' }}</h1>
        <p class="role">{{ $payload['headline'] ?? '' }}</p>
        <h2>Kontakt</h2>
        <p>
            {{ $payload['location'] ?? '' }}<br>
            {{ $payload['email'] ?? '' }}<br>
            {{ $payload['phone'] ?? '' }}<br>
            {{ $payload['website'] ?? '' }}
        </p>
        @if (!empty($payload['equity_capital']))
            <h2>Eigenkapital</h2>
            <p>{{ $payload['equity_capital'] }}</p>
        @endif
        @foreach (['skills' => 'Kompetenzen', 'languages' => 'Sprachen', 'diplomas' => 'Diplome'] as $key => $label)
            @if (!empty($payload[$key]) && is_array($payload[$key]))
                <h2>{{ $label }}</h2>
                <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
        @endforeach
    </aside>
    <section class="main">
        @if (!empty($payload['summary']))
            <h2>Zusammenfassung</h2>
            <p>{!! nl2br(e($payload['summary'])) !!}</p>
        @endif
        @if (!empty($payload['services']))
            <h2>Leistungen</h2>
            <p>{!! nl2br(e($payload['services'])) !!}</p>
        @endif
        @foreach (['experience_areas'=>'Erfahrungsbereiche','experience'=>'Erfahrung','education'=>'Ausbildung','criteria'=>'Kriterien'] as $key => $label)
            @if (!empty($payload[$key]) && is_array($payload[$key]))
                <h2>{{ $label }}</h2>
                <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
        @endforeach
    </section>
</div>
</body>
</html>
