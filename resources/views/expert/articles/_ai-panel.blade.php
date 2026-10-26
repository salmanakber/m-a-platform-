<section class="xp-ai-studio" id="aiStudio">
    <div class="xp-ai-studio__glow" aria-hidden="true"></div>
    <div class="xp-ai-studio__head">
        <div class="xp-ai-studio__badge">KI-Assistent</div>
        <div>
            <h2>Artikel entwerfen lassen</h2>
            <p>Beschreiben Sie das Thema — die KI erstellt Titel, Auszug und fertigen Beitrag. Danach können Sie alles im Editor verfeinern.</p>
        </div>
    </div>

    <div class="xp-ai-studio__grid">
        <div class="form-group xp-ai-studio__topic">
            <label for="ai_topic">Ihr Thema / Briefing</label>
            <textarea class="form-control" id="ai_topic" rows="4" placeholder="z. B. Wie bereite ich mein KMU auf eine Unternehmensnachfolge vor? Welche Schritte sind in den ersten 90 Tagen wichtig?"></textarea>
        </div>
        <div class="xp-ai-studio__meta">
            <div class="form-group">
                <label for="ai_tone">Tonalität</label>
                <select class="form-control" id="ai_tone">
                    <option value="fachlich, klar, vertrauenswürdig">Fachlich &amp; klar</option>
                    <option value="praxisnah und verständlich für KMU-Inhaber">Praxisnah für KMU</option>
                    <option value="kurz und prägnant">Kurz &amp; prägnant</option>
                </select>
            </div>
            <button type="button" class="btn xp-ai-studio__btn" id="aiGenerateBtn">
                <span class="xp-ai-studio__btn-icon" aria-hidden="true">✦</span>
                <span class="xp-ai-studio__btn-label">Entwurf erzeugen</span>
            </button>
        </div>
    </div>

    <div class="xp-ai-studio__status" id="aiStatus" aria-live="polite" hidden></div>
</section>

<script>
(function () {
    var btn = document.getElementById('aiGenerateBtn');
    var statusEl = document.getElementById('aiStatus');
    if (!btn) return;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setBody(html) {
        if (window.tinymce && tinymce.get('body')) {
            tinymce.get('body').setContent(html || '');
            return;
        }
        var el = document.getElementById('body');
        if (el) el.value = html || '';
    }

    function showStatus(message, type) {
        statusEl.hidden = false;
        statusEl.className = 'xp-ai-studio__status' + (type ? ' xp-ai-studio__status--' + type : '');
        statusEl.textContent = message;
    }

    var stages = [
        { id: 'brief', label: 'Briefing verstehen', log: 'Thema wird analysiert…', doneLog: 'Briefing erfasst', detail: 'Schlüsselpunkte extrahieren', ms: 1400 },
        { id: 'structure', label: 'Struktur & Titel', log: 'Gliederung und Titel entstehen…', doneLog: 'Struktur bereit', detail: 'Überschriften planen' },
        { id: 'write', label: 'Inhalt schreiben', log: 'Absätze werden ausgearbeitet…', doneLog: 'Text fertig', detail: 'KI formuliert den Beitrag' },
        { id: 'polish', label: 'Feinschliff', log: 'Auszug und Tonalität abstimmen…', doneLog: 'Feinschliff erledigt', detail: 'Excerpt & Ton' }
    ];

    btn.addEventListener('click', function () {
        var topic = ((document.getElementById('ai_topic') || {}).value || '').trim();
        if (topic.length < 8) {
            showStatus('Bitte beschreiben Sie das Thema mit mindestens 8 Zeichen.', 'error');
            return;
        }

        if (!window.LiveProgress) {
            showStatus('Fortschrittsanzeige nicht geladen — bitte Seite neu laden.', 'error');
            return;
        }

        btn.disabled = true;
        statusEl.hidden = true;

        var request = fetch(@json($aiGenerateUrl ?? route('expert.articles.generate-ai')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                topic: topic,
                tone: (document.getElementById('ai_tone') || {}).value || null,
                category_id: (document.getElementById('category_id') || {}).value || null
            })
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        });

        LiveProgress.runAiStages(stages, request).then(function (result) {
            if (!result.ok) {
                throw new Error((result.data && result.data.message) || 'Die Generierung ist fehlgeschlagen. Bitte später erneut versuchen.');
            }
            var data = result.data;
            if (data.title) document.getElementById('title').value = data.title;
            if (data.excerpt) document.getElementById('excerpt').value = data.excerpt;
            if (data.body) setBody(data.body);
            if (data.category_id && document.getElementById('category_id')) {
                document.getElementById('category_id').value = String(data.category_id);
            }
            LiveProgress.log('Titel: ' + (data.title || '—'), 'ok');
            LiveProgress.log('Text in den Editor eingefügt', 'ok');
            LiveProgress.finish({
                ok: true,
                title: 'Entwurf bereit',
                message: 'Artikel eingefügt — bitte im Editor prüfen und speichern.',
                actionsHtml: '<button type="button" class="btn btn--small" data-lp-close>Zum Editor</button>',
                onClose: function () {
                    var form = document.getElementById('articleForm');
                    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            showStatus('Entwurf eingefügt — prüfen Sie den Text im Editor und veröffentlichen Sie ihn.', 'ok');
        }).catch(function (err) {
            if (window.LiveProgress) {
                LiveProgress.finish({ ok: false, message: err.message || 'Fehler bei der Generierung.' });
            }
            showStatus(err.message || 'Fehler bei der Generierung.', 'error');
        }).finally(function () {
            btn.disabled = false;
        });
    });
})();
</script>
