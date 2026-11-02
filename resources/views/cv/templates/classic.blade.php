<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    <style>
        :root { --ink:#0E4971; --muted:#5a6b78; --line:#d7e3ec; --bg:#f7fafc; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color:#102433; background:#fff; }
        .cv { max-width:820px; margin:0 auto; padding:36px 40px; }
        .cv__top { display:flex; gap:24px; align-items:flex-start; border-bottom:2px solid var(--ink); padding-bottom:18px; margin-bottom:22px; }
        .cv__photo { width:96px; height:96px; object-fit:cover; border-radius:8px; background:var(--bg); }
        .cv__name { font-size:28px; margin:0 0 4px; color:var(--ink); letter-spacing:-0.02em; }
        .cv__headline { margin:0 0 8px; color:var(--muted); font-size:15px; }
        .cv__meta { margin:0; font-size:13px; color:var(--muted); line-height:1.55; }
        h2 { font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:var(--ink); margin:22px 0 8px; border-bottom:1px solid var(--line); padding-bottom:4px; }
        p, li { font-size:14px; line-height:1.55; }
        ul { margin:0; padding-left:1.1rem; }
        .muted { color:var(--muted); }
        @media print { body { background:#fff; } .cv { padding:0; } }
    </style>
</head>
<body>
@php
    $photo = $payload['photo_path'] ?? null;
@endphp
<main class="cv">
    <header class="cv__top">
        @if ($photo)
            <img class="cv__photo" src="{{ asset('storage/'.$photo) }}" alt="">
        @endif
        <div>
            <h1 class="cv__name">{{ $payload['full_name'] ?? '' }}</h1>
            <p class="cv__headline">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
            <p class="cv__meta">
                {{ $payload['location'] ?? '' }}
                @if(!empty($payload['email'])) · {{ $payload['email'] }}@endif
                @if(!empty($payload['phone'])) · {{ $payload['phone'] }}@endif
                @if(!empty($payload['website'])) · {{ $payload['website'] }}@endif
                @if(!empty($payload['equity_capital'])) · Eigenkapital: {{ $payload['equity_capital'] }}@endif
            </p>
        </div>
    </header>

    @if (!empty($payload['summary']))
        <h2>Profil</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @endif

    @if (!empty($payload['services']))
        <h2>Leistungen</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
    @endif

    @foreach ([
        'experience_areas' => 'Erfahrungsbereiche',
        'experience' => 'Berufserfahrung',
        'education' => 'Ausbildung',
        'diplomas' => 'Diplome',
        'criteria' => 'Kriterien',
        'skills' => 'Kompetenzen',
        'languages' => 'Sprachen',
    ] as $key => $label)
        @if (!empty($payload[$key]) && is_array($payload[$key]))
            <h2>{{ $label }}</h2>
            <ul>
                @foreach ($payload[$key] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    @endforeach
</main>
</body>
</html>
