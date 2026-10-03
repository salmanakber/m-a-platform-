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

    <div class="xp-ai-overlay" id="aiOverlay" hidden>
        <div class="xp-ai-overlay__card">
            <div class="xp-ai-loader" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
            <strong id="aiOverlayTitle">Entwurf wird erstellt…</strong>
            <p id="aiOverlayHint">Thema analysieren, Struktur aufbauen, Text ausformulieren.</p>
            <ul class="xp-ai-steps" id="aiSteps">
                <li data-step="1" class="is-active">Briefing verstehen</li>
                <li data-step="2">Struktur &amp; Titel</li>
                <li data-step="3">Inhalt schreiben</li>
            </ul>
        </div>
    </div>
</section>

<script>
(function () {
    var btn = document.getElementById('aiGenerateBtn');
    var statusEl = document.getElementById('aiStatus');
    var overlay = document.getElementById('aiOverlay');
    var overlayTitle = document.getElementById('aiOverlayTitle');
    var overlayHint = document.getElementById('aiOverlayHint');
    var steps = document.querySelectorAll('#aiSteps li');
    if (!btn) return;

    var stepTimer = null;

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

    function setStep(n) {
        steps.forEach(function (li) {
            var s = parseInt(li.getAttribute('data-step'), 10);
            li.classList.toggle('is-active', s === n);
            li.classList.toggle('is-done', s < n);
        });
    }

    function startOverlay() {
        overlay.hidden = false;
        document.body.classList.add('xp-ai-busy');
        btn.disabled = true;
        setStep(1);
        overlayTitle.textContent = 'Entwurf wird erstellt…';
        overlayHint.textContent = 'Thema analysieren, Struktur aufbauen, Text ausformulieren.';
        var n = 1;
        stepTimer = setInterval(function () {
            n = Math.min(3, n + 1);
            setStep(n);
            if (n === 2) overlayHint.textContent = 'Titel und Gliederung entstehen…';
            if (n === 3) overlayHint.textContent = 'Absätze werden ausgearbeitet…';
        }, 2200);
    }

    function stopOverlay() {
        overlay.hidden = true;
        document.body.classList.remove('xp-ai-busy');
        btn.disabled = false;
        if (stepTimer) clearInterval(stepTimer);
        stepTimer = null;
    }

    btn.addEventListener('click', function () {
        var topic = ((document.getElementById('ai_topic') || {}).value || '').trim();
        if (topic.length < 8) {
            showStatus('Bitte beschreiben Sie das Thema mit mindestens 8 Zeichen.', 'error');
            return;
        }

        startOverlay();
        showStatus('', '');
        statusEl.hidden = true;

        fetch(@json(route('expert.articles.generate-ai')), {
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
        }).then(function (result) {
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
            var form = document.getElementById('articleForm');
            if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            showStatus('Entwurf eingefügt — prüfen Sie den Text im Editor und veröffentlichen Sie ihn.', 'ok');
        }).catch(function (err) {
            showStatus(err.message || 'Fehler bei der Generierung.', 'error');
        }).finally(function () {
            stopOverlay();
        });
    });
})();
</script>
