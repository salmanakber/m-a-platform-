<section class="xp-ai-studio" id="eventAiStudio" style="margin-bottom:1rem;">
    <div class="xp-ai-studio__head">
        <div class="xp-ai-studio__badge">KI-Assistent</div>
        <div>
            <h2>Event-Entwurf</h2>
            <p>Die KI liefert zuerst Meta-Daten, dann eine formatierte Beschreibung — Datum/Ort bitte prüfen.</p>
        </div>
    </div>
    <div class="xp-ai-studio__grid">
        <div class="form-group xp-ai-studio__topic">
            <label for="ai_event_briefing">Briefing *</label>
            <textarea class="form-control" id="ai_event_briefing" rows="3" placeholder="z. B. Workshop Nachfolge für KMU-Inhaber, Thema Bewertung, in Zürich"></textarea>
        </div>
        <div class="xp-ai-studio__meta">
            <div class="form-group">
                <label for="ai_event_type">Typ-Hinweis</label>
                <select class="form-control" id="ai_event_type">
                    <option value="workshop">Workshop</option>
                    <option value="presentation">Vortrag</option>
                    <option value="hearing">Hearing</option>
                    <option value="other">Sonstiges</option>
                </select>
            </div>
            <button type="button" class="btn xp-ai-studio__btn" id="aiEventBtn">Entwurf erzeugen</button>
        </div>
    </div>
    <div class="xp-ai-studio__status" id="aiEventStatus" hidden></div>
</section>
<script>
(function () {
    var btn = document.getElementById('aiEventBtn');
    var statusEl = document.getElementById('aiEventStatus');
    if (!btn) return;
    var endpoint = @json($aiGenerateUrl ?? route('expert.events.generate-ai'));

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function setDescription(html) {
        if (typeof window.setRichContent === 'function' && window.setRichContent('description', html)) return;
        if (window.tinymce && tinymce.get('description')) {
            tinymce.get('description').setContent(html || '');
            tinymce.get('description').save();
            return;
        }
        var el = document.getElementById('description');
        if (el) el.value = html || '';
    }

    function post(payload) {
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().then(function (d) {
                if (!r.ok) throw new Error((d && d.message) || 'Fehler');
                return d;
            });
        });
    }

    btn.addEventListener('click', function () {
        var briefing = (document.getElementById('ai_event_briefing').value || '').trim();
        if (briefing.length < 8) {
            statusEl.hidden = false;
            statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--error';
            statusEl.textContent = 'Bitte ein Briefing mit mind. 8 Zeichen.';
            return;
        }
        if (!window.LiveProgress) {
            statusEl.hidden = false;
            statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--error';
            statusEl.textContent = 'Fortschrittsanzeige nicht geladen — Seite neu laden.';
            return;
        }

        var eventType = document.getElementById('ai_event_type').value;
        btn.disabled = true;
        statusEl.hidden = true;

        LiveProgress.open({
            title: 'KI erstellt Event…',
            subtitle: 'Echte Meta-Daten, dann formatierte Beschreibung',
            steps: [
                { id: 'brief', label: 'Briefing' },
                { id: 'meta', label: 'Titel & Meta' },
                { id: 'desc', label: 'Beschreibung schreiben' },
                { id: 'insert', label: 'In Formular einfügen' }
            ],
            startLog: 'Briefing: «' + briefing.slice(0, 80) + (briefing.length > 80 ? '…' : '') + '»'
        });
        LiveProgress.activate('brief');
        LiveProgress.setPercent(10);
        LiveProgress.done('brief');
        LiveProgress.activate('meta', 'Meta-Daten…');
        LiveProgress.log('KI erstellt Titel und Schwerpunkte…', 'working');
        LiveProgress.setPercent(22);

        post({ phase: 'outline', briefing: briefing, event_type: eventType })
            .then(function (meta) {
                LiveProgress.done('meta', meta.title || 'fertig');
                LiveProgress.log('Titel: ' + (meta.title || '—'), 'ok');
                LiveProgress.log('Typ: ' + (meta.event_type || eventType), 'info');
                (meta.outline || []).forEach(function (p, i) {
                    LiveProgress.log('Schwerpunkt ' + (i + 1) + ': ' + p, 'info');
                });
                if (meta.needs_review) LiveProgress.log('Prüfung nötig: Datum/Ort manuell ergänzen', 'warn');

                if (meta.title) document.getElementById('title').value = meta.title;
                if (meta.event_type) document.getElementById('event_type').value = meta.event_type;
                if (meta.location_name) document.getElementById('location_name').value = meta.location_name;
                if (meta.city) document.getElementById('city').value = meta.city;
                if (meta.starts_on) document.getElementById('starts_on').value = meta.starts_on;
                if (meta.starts_at_time) document.getElementById('starts_at_time').value = meta.starts_at_time;
                if (meta.source_url) document.getElementById('source_url').value = meta.source_url;

                LiveProgress.activate('desc', 'Beschreibung…');
                LiveProgress.setPercent(50);
                LiveProgress.log('Schreibe formatierte Beschreibung…', 'working');

                return post({
                    phase: 'description',
                    briefing: briefing,
                    event_type: meta.event_type || eventType,
                    title: meta.title,
                    outline: meta.outline || []
                }).then(function (desc) {
                    return { meta: meta, description: desc.description };
                });
            })
            .then(function (result) {
                LiveProgress.done('desc');
                LiveProgress.activate('insert');
                LiveProgress.setPercent(90);
                setDescription(result.description);
                LiveProgress.done('insert');
                LiveProgress.setPercent(100);
                LiveProgress.log('Formatierte Beschreibung eingefügt', 'ok');
                LiveProgress.finish({
                    ok: true,
                    title: 'Event-Entwurf bereit',
                    message: (result.meta.notes || 'Entwurf eingefügt.') + (result.meta.needs_review ? ' Bitte Datum/Ort prüfen.' : ''),
                    actionsHtml: '<button type="button" class="btn btn--small" data-lp-close>Zum Formular</button>'
                });
                statusEl.hidden = false;
                statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--ok';
                statusEl.textContent = (result.meta.notes || 'Entwurf eingefügt.') + (result.meta.needs_review ? ' Bitte Datum/Ort prüfen.' : '');
            })
            .catch(function (e) {
                LiveProgress.finish({ ok: false, message: e.message || 'Fehler' });
                statusEl.hidden = false;
                statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--error';
                statusEl.textContent = e.message || 'Fehler';
            })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
