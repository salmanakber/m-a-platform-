@extends('layouts.admin')

@section('title', 'Vorlage bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.email-templates.index') }}">E-Mail-Vorlagen</a>
    <span>/</span>
    <span>{{ $template->key }}</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">E-Mail</p>
            <h1><code>{{ $template->key }}</code></h1>
            <p>Inhalt bearbeiten und Vorschau mit dem echten E-Mail-Layout prüfen.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.email-templates.index') }}">Alle Vorlagen</a>
        </div>
    </header>

    @include('partials.flash')

    <div class="xp-crawl-layout">
        <form method="post" action="{{ route('admin.email-templates.update', $template) }}" class="admin-card" id="templateForm">
            @csrf
            @method('PUT')
            <div class="admin-card__head">
                <h2>Inhalt</h2>
                <p>Platzhalter wie <code>@{{owner_name}}</code> bleiben erhalten.</p>
            </div>
            <div style="padding:1.25rem;">
                <div class="form-group">
                    <label for="subject">Betreff</label>
                    <input class="form-control" type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" required>
                </div>
                <div class="form-group">
                    <label for="body_html">HTML-Inhalt (innerer Block)</label>
                    <textarea class="form-control" name="body_html" id="body_html" rows="14" required style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:0.82rem;line-height:1.45;">{{ old('body_html', $template->body_html) }}</textarea>
                </div>
                <div class="form-group">
                    <label for="body_text">Text-Inhalt <span class="label-optional">(optional)</span></label>
                    <textarea class="form-control" name="body_text" id="body_text" rows="5">{{ old('body_text', $template->body_text) }}</textarea>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.6rem;">
                    <button type="submit" class="btn">Speichern</button>
                    <button type="button" class="btn btn--ghost" id="previewBtn">Vorschau aktualisieren</button>
                </div>
            </div>
        </form>

        <aside class="xp-side">
            <section class="admin-card">
                <div class="admin-card__head">
                    <h2>Live-Vorschau</h2>
                    <p>Mit Beispieldaten gerendert.</p>
                </div>
                <div style="padding:0.75rem 1rem 1rem;">
                    <iframe id="previewFrame" title="E-Mail-Vorschau" style="width:100%;min-height:420px;border:1px solid var(--line-soft);background:#fff;"></iframe>
                </div>
            </section>
            <script type="application/json" id="initialPreviewHtml">@json($previewHtml)</script>

            <form method="post" action="{{ route('admin.email-templates.test', $template) }}" class="admin-card" style="margin-top:1rem;">
                @csrf
                <div class="admin-card__head">
                    <h2>Testmail</h2>
                    <p>Sendet die aktuelle Vorlage (gespeicherter Stand, falls nicht mitgesendet).</p>
                </div>
                <div style="padding:1rem 1.25rem 1.25rem;">
                    <div class="form-group">
                        <label for="test_email">Empfänger</label>
                        <input class="form-control" type="email" name="test_email" id="test_email" value="{{ auth()->user()->email }}" required>
                    </div>
                    <input type="hidden" name="subject" id="test_subject" value="{{ old('subject', $template->subject) }}">
                    <input type="hidden" name="body_html" id="test_body" value="">
                    <button type="submit" class="btn btn--ghost btn--small">Testmail senden</button>
                </div>
            </form>
        </aside>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('previewBtn');
    var frame = document.getElementById('previewFrame');
    var subject = document.getElementById('subject');
    var body = document.getElementById('body_html');
    var testBody = document.getElementById('test_body');
    var testSubject = document.getElementById('test_subject');
    if (testBody) testBody.value = body ? body.value : '';
    var initial = document.getElementById('initialPreviewHtml');
    if (frame && initial) {
        try { frame.srcdoc = JSON.parse(initial.textContent); } catch (e) {}
    }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function syncTestFields() {
        if (testSubject) testSubject.value = subject.value;
        if (testBody) testBody.value = body.value;
    }
    if (body) body.addEventListener('input', syncTestFields);
    if (subject) subject.addEventListener('input', syncTestFields);

    if (!btn || !frame) return;
    btn.addEventListener('click', function () {
        syncTestFields();
        btn.disabled = true;
        fetch(@json(route('admin.email-templates.preview', $template)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ subject: subject.value, body_html: body.value })
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              frame.srcdoc = data.html || '';
          }).catch(function () {
              alert('Vorschau fehlgeschlagen.');
          }).finally(function () { btn.disabled = false; });
    });
})();
</script>
@endsection
