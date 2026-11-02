@php
    $p = $payload ?? [];
    $formAction = $formAction ?? '#';
    $aiAction = $aiAction ?? null;
    $previewUrl = $previewUrl ?? null;
    $finalizeAction = $finalizeAction ?? null;
    $backUrl = $backUrl ?? '#';
@endphp

<div class="admin-page" style="max-width:980px;">
    <header class="admin-page-hero" style="margin-bottom:1rem;">
        <div>
            <p class="page-kicker">CV Generator</p>
            <h1 style="margin:0;">{{ $cv->title ?: 'Lebenslauf' }}</h1>
            <p style="margin:0.35rem 0 0;">Profil vorausgefüllt — Felder bearbeiten, optional KI nutzen, Template wählen, dann finalisieren.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ $backUrl }}">Zurück</a>
            @if ($previewUrl)
                <a class="btn btn--ghost btn--small" href="{{ $previewUrl }}" target="_blank" rel="noopener">Vorschau</a>
            @endif
        </div>
    </header>

    <form method="post" action="{{ $formAction }}" class="card">
        @csrf
        @method('PUT')
        <div class="form-row">
            <div class="form-group">
                <label for="title">Titel</label>
                <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $cv->title) }}">
            </div>
            <div class="form-group">
                <label for="template_key">Template</label>
                <select class="form-control" name="template_key" id="template_key">
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}" @selected(old('template_key', $cv->template_key) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Name</label>
                <input class="form-control" name="full_name" id="full_name" value="{{ old('full_name', $p['full_name'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="headline">Headline / Rolle</label>
                <input class="form-control" name="headline" id="headline" value="{{ old('headline', $p['headline'] ?? '') }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="company">Unternehmen</label>
                <input class="form-control" name="company" id="company" value="{{ old('company', $p['company'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="location">Standort</label>
                <input class="form-control" name="location" id="location" value="{{ old('location', $p['location'] ?? '') }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $p['email'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input class="form-control" name="phone" id="phone" value="{{ old('phone', $p['phone'] ?? '') }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="website">Website</label>
                <input class="form-control" name="website" id="website" value="{{ old('website', $p['website'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="equity_capital">Eigenkapital</label>
                <input class="form-control" name="equity_capital" id="equity_capital" value="{{ old('equity_capital', $p['equity_capital'] ?? '') }}">
            </div>
        </div>
        <div class="form-group">
            <label for="summary">Professionelle Zusammenfassung</label>
            <textarea class="form-control" name="summary" id="summary" rows="5">{{ old('summary', $p['summary'] ?? '') }}</textarea>
        </div>
        <div class="form-group">
            <label for="services">Leistungen</label>
            <textarea class="form-control" name="services" id="services" rows="4">{{ old('services', $p['services'] ?? '') }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="experience">Berufserfahrung (eine Zeile pro Eintrag)</label>
                <textarea class="form-control" name="experience" id="experience" rows="5">{{ old('experience', \App\Support\TextLines::toTextarea($p['experience'] ?? [])) }}</textarea>
            </div>
            <div class="form-group">
                <label for="education">Ausbildung</label>
                <textarea class="form-control" name="education" id="education" rows="5">{{ old('education', \App\Support\TextLines::toTextarea($p['education'] ?? [])) }}</textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="experience_areas">Erfahrungsbereiche</label>
                <textarea class="form-control" name="experience_areas" id="experience_areas" rows="4">{{ old('experience_areas', \App\Support\TextLines::toTextarea($p['experience_areas'] ?? [])) }}</textarea>
            </div>
            <div class="form-group">
                <label for="diplomas">Diplome</label>
                <textarea class="form-control" name="diplomas" id="diplomas" rows="4">{{ old('diplomas', \App\Support\TextLines::toTextarea($p['diplomas'] ?? [])) }}</textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="skills">Kompetenzen</label>
                <textarea class="form-control" name="skills" id="skills" rows="3">{{ old('skills', \App\Support\TextLines::toTextarea($p['skills'] ?? [])) }}</textarea>
            </div>
            <div class="form-group">
                <label for="languages">Sprachen</label>
                <textarea class="form-control" name="languages" id="languages" rows="3">{{ old('languages', \App\Support\TextLines::toTextarea($p['languages'] ?? [])) }}</textarea>
            </div>
        </div>
        <div class="form-group">
            <label for="criteria">Kriterien / Präferenzen</label>
            <textarea class="form-control" name="criteria" id="criteria" rows="3">{{ old('criteria', \App\Support\TextLines::toTextarea($p['criteria'] ?? [])) }}</textarea>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>

    @if ($finalizeAction)
        <form method="post" action="{{ $finalizeAction }}" style="margin-top:0.75rem;">
            @csrf
            <button type="submit" class="btn btn--ghost">Finalisieren (HTML / Druck-PDF)</button>
        </form>
    @endif

    @if ($aiAction)
        <form method="post" action="{{ $aiAction }}" class="card" style="margin-top:1rem;">
            @csrf
            <h2 style="margin-top:0;font-size:1.05rem;">KI-Inhalt erzeugen</h2>
            <p class="field-hint">Schreibt Vorschläge in das gewählte Feld. Danach speichern und prüfen.</p>
            <div class="form-row">
                <div class="form-group">
                    <label for="field">Feld</label>
                    <select class="form-control" name="field" id="field">
                        <option value="summary">Zusammenfassung</option>
                        <option value="services">Leistungen</option>
                        <option value="experience">Berufserfahrung</option>
                        <option value="education">Ausbildung</option>
                        <option value="skills">Kompetenzen</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes">Zusatzhinweise (optional)</label>
                    <input class="form-control" type="text" name="notes" id="notes" placeholder="z. B. Fokus auf Nachfolge KMU">
                </div>
            </div>
            <button type="submit" class="btn">Mit KI generieren</button>
        </form>
    @endif

    @if ($cv->pdf_path)
        <p style="margin-top:1rem;">
            Finalisierte Version:
            <a href="{{ asset('storage/'.$cv->pdf_path) }}" target="_blank" rel="noopener">HTML öffnen / als PDF drucken</a>
        </p>
    @endif
</div>
