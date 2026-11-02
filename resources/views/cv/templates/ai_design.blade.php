<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['full_name'] ?? 'Lebenslauf' }}</title>
    @php
        $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971');
        $layout = $layout ?? ($payload['layout'] ?? []);
        $style = $layout['style'] ?? 'centered';
        $density = $layout['density'] ?? 'normal';
        $mood = $layout['type_mood'] ?? 'sans';
        $pad = $density === 'airy' ? '48px 52px' : ($density === 'compact' ? '24px 28px' : '36px 40px');
        $fs = $density === 'compact' ? '13px' : '14.5px';
        $font = $mood === 'serif'
            ? 'Georgia, "Times New Roman", serif'
            : ($mood === 'mixed'
                ? 'Georgia, "Times New Roman", serif'
                : '"Helvetica Neue", Helvetica, Arial, sans-serif');
        $hFont = $mood === 'serif'
            ? 'Georgia, "Times New Roman", serif'
            : '"Helvetica Neue", Helvetica, Arial, sans-serif';
    @endphp
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef2f7;color:#0f172a;font-family:{{ $font }};font-size:{{ $fs }}}
        .sheet{max-width:900px;margin:20px auto;background:#fff;box-shadow:0 14px 40px rgba(15,23,42,.1);overflow:hidden}
        h1{margin:0;font-family:{{ $hFont }};letter-spacing:-.03em;line-height:1.15}
        h2{margin:0 0 8px;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:{{ $accent }}}
        p,li{line-height:1.55;margin:0 0 .55rem}
        ul{margin:0 0 1rem;padding-left:1.1rem}
        .muted{color:#64748b}
        /* centered */
        .style-centered{padding:{{ $pad }}}
        .style-centered h1{font-size:30px;color:{{ $accent }}}
        /* banner */
        .style-banner .banner{background:{{ $accent }};color:#fff;padding:32px 40px}
        .style-banner .banner h1{font-size:30px;color:#fff}
        .style-banner .banner .muted{color:rgba(255,255,255,.85)}
        .style-banner .content{padding:{{ $pad }}}
        /* sidebar */
        .style-sidebar_left,.style-sidebar_right{display:grid;min-height:980px}
        .style-sidebar_left{grid-template-columns:270px 1fr}
        .style-sidebar_right{grid-template-columns:1fr 270px}
        .side{background:{{ $accent }};color:#eef6fb;padding:32px 26px}
        .side h1{font-size:24px;color:#fff}
        .side h2{color:rgba(255,255,255,.7)}
        .side .muted,.side p,.side li{color:rgba(238,246,251,.9);font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px}
        .main{padding:{{ $pad }}}
        /* split */
        .style-split{padding:{{ $pad }}}
        .style-split .top{border-bottom:2px solid {{ $accent }};padding-bottom:16px;margin-bottom:20px}
        .style-split .cols{display:grid;grid-template-columns:1.15fr .85fr;gap:24px}
        .photo{width:88px;height:88px;object-fit:cover;border-radius:8px}
        @media print{body{background:#fff}.sheet{margin:0;box-shadow:none}}
        @media (max-width:720px){
            .style-sidebar_left,.style-sidebar_right,.style-split .cols{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
@php $photo = $payload['photo_path'] ?? null; @endphp
<div class="sheet style-{{ $style }}">
@if ($style === 'banner')
    <div class="banner">
        <div style="display:flex;gap:18px;align-items:center">
            @if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
            <div>
                <h1>{{ $payload['full_name'] ?? '' }}</h1>
                <p class="muted" style="margin-top:6px">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
                <p class="muted">{{ $payload['location'] ?? '' }} · {{ $payload['email'] ?? '' }} · {{ $payload['phone'] ?? '' }}</p>
            </div>
        </div>
    </div>
    <div class="content">
        @include('cv.templates._sections', ['payload' => $payload, 'mode' => 'main'])
    </div>
@elseif (in_array($style, ['sidebar_left', 'sidebar_right'], true))
    @php
        $sideHtml = view('cv.templates._sections', ['payload' => $payload, 'mode' => 'side'])->render();
        $mainHtml = view('cv.templates._sections', ['payload' => $payload, 'mode' => 'main'])->render();
    @endphp
    @if ($style === 'sidebar_left')
        <aside class="side">
            @if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="" style="width:100%;aspect-ratio:1;margin-bottom:14px">@endif
            <h1>{{ $payload['full_name'] ?? '' }}</h1>
            <p class="muted" style="margin:8px 0 14px">{{ $payload['headline'] ?? '' }}</p>
            <h2>Kontakt</h2>
            <p>{{ $payload['location'] ?? '' }}<br>{{ $payload['email'] ?? '' }}<br>{{ $payload['phone'] ?? '' }}<br>{{ $payload['website'] ?? '' }}</p>
            {!! $sideHtml !!}
        </aside>
        <section class="main">{!! $mainHtml !!}</section>
    @else
        <section class="main">
            <h1 style="color:{{ $accent }};font-size:28px;margin-bottom:6px">{{ $payload['full_name'] ?? '' }}</h1>
            <p class="muted">{{ $payload['headline'] ?? '' }} · {{ $payload['location'] ?? '' }}</p>
            {!! $mainHtml !!}
        </section>
        <aside class="side">
            @if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="" style="width:100%;aspect-ratio:1;margin-bottom:14px">@endif
            <h2>Kontakt</h2>
            <p>{{ $payload['email'] ?? '' }}<br>{{ $payload['phone'] ?? '' }}<br>{{ $payload['website'] ?? '' }}</p>
            {!! $sideHtml !!}
        </aside>
    @endif
@elseif ($style === 'split')
    <div class="top">
        <h1 style="font-size:30px;color:{{ $accent }}">{{ $payload['full_name'] ?? '' }}</h1>
        <p class="muted" style="margin-top:6px">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
        <p class="muted">{{ $payload['location'] ?? '' }} · {{ $payload['email'] ?? '' }} · {{ $payload['phone'] ?? '' }}</p>
    </div>
    <div class="cols">
        <div>@include('cv.templates._sections', ['payload' => $payload, 'mode' => 'left'])</div>
        <div>@include('cv.templates._sections', ['payload' => $payload, 'mode' => 'right'])</div>
    </div>
@else
    <div class="style-centered">
        <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:22px">
            <div>
                <h1>{{ $payload['full_name'] ?? '' }}</h1>
                <p class="muted" style="margin-top:6px">{{ $payload['headline'] ?? '' }}@if(!empty($payload['company'])) · {{ $payload['company'] }}@endif</p>
                <p class="muted">{{ $payload['location'] ?? '' }} · {{ $payload['email'] ?? '' }} · {{ $payload['phone'] ?? '' }}</p>
            </div>
            @if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="">@endif
        </div>
        @include('cv.templates._sections', ['payload' => $payload, 'mode' => 'main'])
    </div>
@endif
</div>
</body>
</html>
