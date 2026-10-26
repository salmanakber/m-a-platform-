<section class="xp-ai-studio" id="aiStudio">
    <div class="xp-ai-studio__glow" aria-hidden="true"></div>
    <div class="xp-ai-studio__head">
        <div class="xp-ai-studio__badge">KI-Assistent</div>
        <div>
            <h2>Artikel entwerfen lassen</h2>
            <p>Beschreiben Sie das Thema — die KI erstellt zuerst Struktur, dann formatierten Text für den Editor.</p>
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
    var endpoint = @json($aiGenerateUrl ?? route('expert.articles.generate-ai'));

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showStatus(message, type) {
        statusEl.hidden = false;
        statusEl.className = 'xp-ai-studio__status' + (type ? ' xp-ai-studio__status--' + type : '');
        statusEl.textContent = message;
    }

    function setBody(html) {
        if (typeof window.setRichContent === 'function' && window.setRichContent('body', html)) return;
        if (window.tinymce && tinymce.get('body')) {
            tinymce.get('body').setContent(html || '');
            tinymce.get('body').save();
            return;
        }
        var el = document.getElementById('body');
        if (el) el.value = html || '';
    }

    function post(payload) {
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw new Error((data && data.message) || 'Generierung fehlgeschlagen.');
                return data;
            });
        });
    }

    btn.addEventListener('click', function () {
        var topic = ((document.getElementById('ai_topic') || {}).value || '').trim();
        if (topic.length < 8) {
            showStatus('Bitte beschreiben Sie das Thema mit mindestens 8 Zeichen.', 'error');
            return;
        }
        if (!window.LiveProgress) {
            showStatus('Fortschrittsanzeige nicht geladen — Seite neu laden.', 'error');
            return;
        }

        var tone = (document.getElementById('ai_tone') || {}).value || null;
        var categoryId = (document.getElementById('category_id') || {}).value || null;

        btn.disabled = true;
        statusEl.hidden = true;

        LiveProgress.open({
            title: 'KI schreibt Artikel…',
            subtitle: 'Echte Zwischenergebnisse in Echtzeit',
            steps: [
                { id: 'brief', label: 'Briefing' },
                { id: 'outline', label: 'Titel & Gliederung' },
                { id: 'write', label: 'Abschnitte schreiben' },
                { id: 'insert', label: 'In Editor einfügen' }
            ],
            startLog: 'Briefing: «' + topic.slice(0, 80) + (topic.length > 80 ? '…' : '') + '»'
        });
        LiveProgress.activate('brief', 'Thema erfassen');
        LiveProgress.setPercent(8);
        LiveProgress.done('brief', 'bereit');
        LiveProgress.activate('outline', 'Struktur wird erzeugt…');
        LiveProgress.log('KI erstellt Titel und Gliederung…', 'working');
        LiveProgress.setPercent(18);

        post({
            phase: 'outline',
            topic: topic,
            tone: tone,
            category_id: categoryId
        }).then(function (outline) {
            LiveProgress.done('outline', outline.title || 'fertig');
            LiveProgress.log('Titel: ' + (outline.title || '—'), 'ok');
            if (outline.excerpt) LiveProgress.log('Auszug: ' + outline.excerpt.slice(0, 100) + (outline.excerpt.length > 100 ? '…' : ''), 'ok');
            (outline.headings || []).forEach(function (h, i) {
                LiveProgress.log('Abschnitt ' + (i + 1) + ': ' + h, 'info');
            });

            if (outline.title) document.getElementById('title').value = outline.title;
            if (outline.excerpt) document.getElementById('excerpt').value = outline.excerpt;
            if (outline.category_id && document.getElementById('category_id')) {
                document.getElementById('category_id').value = String(outline.category_id);
            }

            LiveProgress.activate('write', 'Fliesstext entsteht…');
            LiveProgress.setPercent(42);
            LiveProgress.log('Schreibe formatierte Absätze…', 'working');

            var headingIdx = 0;
            var headingTimer = setInterval(function () {
                var list = outline.headings || [];
                if (headingIdx >= list.length) return;
                LiveProgress.log('Ausarbeiten: ' + list[headingIdx], 'working');
                LiveProgress.setPercent(42 + Math.min(40, Math.round(((headingIdx + 1) / Math.max(list.length, 1)) * 40)));
                headingIdx += 1;
            }, 900);

            return post({
                phase: 'body',
                topic: topic,
                tone: tone,
                title: outline.title,
                excerpt: outline.excerpt,
                headings: outline.headings || []
            }).then(function (bodyRes) {
                clearInterval(headingTimer);
                return { outline: outline, body: bodyRes.body };
            }, function (err) {
                clearInterval(headingTimer);
                throw err;
            });
        }).then(function (result) {
            LiveProgress.done('write', 'Text fertig');
            LiveProgress.activate('insert');
            LiveProgress.setPercent(92);
            LiveProgress.log('Formatierten HTML-Text in den Editor einfügen…', 'working');

            setBody(result.body);
            LiveProgress.done('insert', 'eingefügt');
            LiveProgress.setPercent(100);
            LiveProgress.log('Editor aktualisiert — bitte prüfen und speichern', 'ok');
            LiveProgress.finish({
                ok: true,
                title: 'Entwurf bereit',
                message: 'Formatierter Artikel im Editor — Überschriften & Listen prüfen.',
                actionsHtml: '<button type="button" class="btn btn--small" data-lp-close>Zum Editor</button>',
                onClose: function () {
                    var form = document.getElementById('articleForm');
                    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            showStatus('Formatierter Entwurf eingefügt — im Editor prüfen und veröffentlichen.', 'ok');
        }).catch(function (err) {
            LiveProgress.finish({ ok: false, message: err.message || 'Fehler bei der Generierung.' });
            showStatus(err.message || 'Fehler bei der Generierung.', 'error');
        }).finally(function () {
            btn.disabled = false;
        });
    });
})();
</script>
