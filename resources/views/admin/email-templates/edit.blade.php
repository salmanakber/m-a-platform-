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
@php
    $labels = [
        'owner_name' => 'Name Interessent',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'message' => 'Nachricht',
        'company_name' => 'Firma',
        'industry' => 'Branche',
        'canton_name' => 'Kanton',
        'expert_company_name' => 'Firma Experte',
        'contact_name' => 'Kontaktname',
        'name' => 'Name',
        'reset_url' => 'Reset-Link',
        'expires_minutes' => 'Ablauf (Min.)',
        'invoice_number' => 'Rechnungsnr.',
        'invoice_date' => 'Rechnungsdatum',
        'amount' => 'Betrag',
        'currency' => 'Währung',
        'payment_status_label' => 'Zahlungsstatus',
        'service_title' => 'Leistung',
        'service_detail' => 'Leistungsdetail',
        'notes' => 'Bemerkung',
    ];
    $templateHeading = str_replace('_', ' ', str_replace('.', ' · ', $template->key));
@endphp
<div class="admin-page mail-studio">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">E-Mail</p>
            <h1>{{ $templateHeading }}</h1>
            <p>Texte bearbeiten wie in einem Editor — Platzhalter einfügen und live sehen, wie die Mail aussieht.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.email-templates.index') }}">Alle Vorlagen</a>
        </div>
    </header>

    @include('partials.flash')

    <div class="mail-studio__grid">
        <form method="post" action="{{ route('admin.email-templates.update', $template) }}" class="sheet mail-studio__editor" id="templateForm">
            @csrf
            @method('PUT')
            <div class="sheet__head">
                <div>
                    <h2>Inhalt bearbeiten</h2>
                    <p class="entity-sub" style="margin:0.25rem 0 0;">Schlüssel: <code>{{ $template->key }}</code></p>
                </div>
            </div>
            <div class="mail-studio__pad">
                <div class="form-group">
                    <label for="subject">Betreff</label>
                    <input class="form-control mail-studio__subject" type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" required>
                </div>

                <div class="mail-studio__tokens" aria-label="Platzhalter">
                    <span class="mail-studio__tokens-label">Platzhalter einfügen</span>
                    <div class="mail-studio__token-list">
                        @foreach ($samplePlaceholders as $key => $sample)
                            @continue($key === 'invoice_document')
                            <button type="button" class="mail-token" data-key="{{ $key }}" title="Beispiel: {{ \Illuminate\Support\Str::limit(strip_tags((string) $sample), 80) }}">
                                {{ $labels[$key] ?? $key }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label for="body_html">E-Mail-Text</label>
                    <textarea class="form-control" name="body_html" id="body_html" rows="16" required>{{ old('body_html', $template->body_html) }}</textarea>
                    <p class="field-hint">
                        Formatieren Sie Absätze und Listen. Platzhalter wie <code>@{{ owner_name }}</code> bleiben beim Speichern erhalten.
                        @if (str_starts_with($template->key, 'invoice_'))
                            Rechnungsvorlagen: <code>@{{ invoice_document }}</code> fügt die formatierte Rechnung ein (nicht manuell ersetzen).
                        @endif
                    </p>
                </div>

                <details class="mail-studio__advanced">
                    <summary>Optional: reiner Text (ohne HTML)</summary>
                    <div class="form-group" style="margin-top:0.75rem;">
                        <textarea class="form-control" name="body_text" id="body_text" rows="4">{{ old('body_text', $template->body_text) }}</textarea>
                    </div>
                </details>

                <div class="mail-studio__actions">
                    <button type="submit" class="btn">Speichern</button>
                    <button type="button" class="btn btn--ghost" id="previewBtn">Vorschau aktualisieren</button>
                </div>
            </div>
        </form>

        <aside class="mail-studio__side">
            <section class="sheet">
                <div class="sheet__head">
                    <div>
                        <h2>Live-Vorschau</h2>
                        <p class="entity-sub" style="margin:0.25rem 0 0;" id="previewSubjectLabel">Mit Beispieldaten</p>
                    </div>
                </div>
                <div class="mail-studio__preview-wrap">
                    <iframe id="previewFrame" title="E-Mail-Vorschau"></iframe>
                </div>
            </section>
            <script type="application/json" id="initialPreviewHtml">@json($previewHtml)</script>

            <form method="post" action="{{ route('admin.email-templates.test', $template) }}" class="sheet" style="margin-top:1rem;" id="testForm">
                @csrf
                <div class="sheet__head">
                    <div>
                        <h2>Testmail</h2>
                        <p class="entity-sub" style="margin:0.25rem 0 0;">Sendet den aktuellen Editor-Inhalt.</p>
                    </div>
                </div>
                <div class="mail-studio__pad">
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

<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    var btn = document.getElementById('previewBtn');
    var frame = document.getElementById('previewFrame');
    var subject = document.getElementById('subject');
    var body = document.getElementById('body_html');
    var testBody = document.getElementById('test_body');
    var testSubject = document.getElementById('test_subject');
    var previewLabel = document.getElementById('previewSubjectLabel');
    var previewUrl = @json(route('admin.email-templates.preview', $template));
    var previewTimer = null;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function editorHtml() {
        if (window.tinymce && tinymce.get('body_html')) {
            return tinymce.get('body_html').getContent();
        }
        return body ? body.value : '';
    }

    function syncTestFields() {
        if (testSubject) testSubject.value = subject.value;
        if (testBody) testBody.value = editorHtml();
    }

    function refreshPreview() {
        syncTestFields();
        if (btn) btn.disabled = true;
        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ subject: subject.value, body_html: editorHtml() })
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              if (frame) frame.srcdoc = data.html || '';
              if (previewLabel && data.subject) previewLabel.textContent = 'Betreff: ' + data.subject;
          }).catch(function () {
              /* keep last preview */
          }).finally(function () { if (btn) btn.disabled = false; });
    }

    function schedulePreview() {
        if (previewTimer) clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 700);
    }

    var initial = document.getElementById('initialPreviewHtml');
    if (frame && initial) {
        try { frame.srcdoc = JSON.parse(initial.textContent); } catch (e) {}
    }
    syncTestFields();

    if (window.tinymce) {
        tinymce.init({
            selector: '#body_html',
            height: 420,
            menubar: false,
            branding: false,
            promotion: false,
            plugins: 'lists link autolink code',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat | code',
            block_formats: 'Absatz=p; Überschrift=h2',
            content_style: 'body{font-family:Manrope,system-ui,sans-serif;font-size:15px;line-height:1.6;color:#1a2b3a;} p{margin:0 0 .85em;} a{color:#0E4971;}',
            setup: function (editor) {
                editor.on('change keyup SetContent', function () {
                    editor.save();
                    syncTestFields();
                    schedulePreview();
                });
            }
        });
    }

    document.querySelectorAll('.mail-token').forEach(function (el) {
        el.addEventListener('click', function () {
            var key = el.getAttribute('data-key') || '';
            var token = '@{{' + key + '}}';
            if (window.tinymce && tinymce.get('body_html')) {
                tinymce.get('body_html').insertContent(token);
            } else if (body) {
                var start = body.selectionStart || 0;
                var end = body.selectionEnd || 0;
                body.value = body.value.slice(0, start) + token + body.value.slice(end);
            }
            syncTestFields();
            schedulePreview();
        });
    });

    if (subject) subject.addEventListener('input', schedulePreview);
    if (btn) btn.addEventListener('click', refreshPreview);

    var testForm = document.getElementById('testForm');
    if (testForm) {
        testForm.addEventListener('submit', function () { syncTestFields(); });
    }
})();
</script>
@endsection
