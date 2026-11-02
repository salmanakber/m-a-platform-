<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($payload['full_name'] ?? 'Lebenslauf') }}</title>
    @php $accent = $accent ?? ($payload['accent_color'] ?? '#0E4971'); @endphp
    <style>
        :root { --accent: {{ $accent }}; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e8eef3;
            color: #12202b;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 20;
            display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; justify-content: space-between;
            padding: .75rem 1rem;
            background: #0E4971; color: #fff;
            box-shadow: 0 8px 24px rgba(14,73,113,.25);
        }
        .toolbar p { margin: 0; font-size: .9rem; opacity: .92; max-width: 42rem; line-height: 1.4; }
        .toolbar strong { display: block; margin-bottom: .15rem; }
        .toolbar .actions { display: flex; gap: .45rem; flex-wrap: wrap; }
        .toolbar button, .toolbar a {
            appearance: none; border: 0; border-radius: 8px; padding: .55rem .9rem;
            font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; color: #0E4971; background: #fff;
        }
        .toolbar button.secondary { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.45); }
        .sheet-wrap { padding: 18px 12px 40px; }
        .sheet {
            width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff;
            padding: 18mm 16mm; box-shadow: 0 12px 36px rgba(15,23,42,.12);
        }
        .head { border-bottom: 2.5pt solid var(--accent); padding-bottom: 10pt; margin-bottom: 14pt; }
        .name { margin: 0; font-size: 22pt; color: var(--accent); letter-spacing: -0.3pt; }
        .role { margin: 4pt 0 0; font-size: 11pt; color: #4a5b68; }
        .meta { margin: 6pt 0 0; font-size: 9.5pt; color: #5a6b78; }
        h2 {
            margin: 13pt 0 5pt; font-size: 9.5pt; letter-spacing: 1pt; text-transform: uppercase;
            color: var(--accent); border-bottom: 0.6pt solid #d7e3ec; padding-bottom: 3pt;
        }
        p { margin: 0 0 7pt; font-size: 10.5pt; line-height: 1.45; }
        ul { margin: 0 0 8pt; padding-left: 14pt; }
        li { margin: 0 0 3pt; font-size: 10.5pt; line-height: 1.45; }
        .two { width: 100%; border-collapse: collapse; }
        .two td { vertical-align: top; width: 50%; padding-right: 10pt; }
        .two td + td { padding-right: 0; padding-left: 10pt; }
        @page { size: A4 portrait; margin: 12mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>Als PDF speichern</strong>
            <p>Klicken Sie «Drucken / PDF», wählen Sie Ziel «Als PDF speichern», dann Speichern. So erhalten Sie eine saubere A4-Datei — ohne Server-Installation.</p>
        </div>
        <div class="actions">
            <button type="button" onclick="window.print()">Drucken / PDF</button>
            <button type="button" class="secondary" onclick="window.close()">Schliessen</button>
        </div>
    </div>

    <div class="sheet-wrap">
        <article class="sheet">
            <header class="head">
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
            </header>

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
        </article>
    </div>

    @if (!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 350);
            });
        </script>
    @endif
</body>
</html>
