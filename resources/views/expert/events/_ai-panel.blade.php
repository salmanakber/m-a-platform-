<section class="xp-ai-studio" id="eventAiStudio" style="margin-bottom:1rem;">
    <div class="xp-ai-studio__head">
        <div class="xp-ai-studio__badge">KI-Assistent</div>
        <div>
            <h2>Event-Entwurf</h2>
            <p>Die KI erstellt nur einen Entwurf. Datum und Ort werden nicht erfunden — bitte prüfen und ergänzen.</p>
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

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    var stages = [
        { id: 'brief', label: 'Briefing lesen', log: 'Event-Briefing wird gelesen…', doneLog: 'Briefing verstanden', detail: 'Typ & Zielgruppe', ms: 1200 },
        { id: 'draft', label: 'Titel & Beschreibung', log: 'Titel und Beschreibung entstehen…', doneLog: 'Textentwurf fertig', detail: 'KI schreibt den Entwurf' },
        { id: 'meta', label: 'Metadaten vorschlagen', log: 'Ort und Typ werden vorgeschlagen…', doneLog: 'Vorschläge bereit', detail: 'Ohne erfundene Daten' }
    ];

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
            statusEl.textContent = 'Fortschrittsanzeige nicht geladen — bitte Seite neu laden.';
            return;
        }

        btn.disabled = true;
        statusEl.hidden = true;

        var request = fetch(@json($aiGenerateUrl ?? route('expert.events.generate-ai')), {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ briefing: briefing, event_type: document.getElementById('ai_event_type').value })
        }).then(function (r) { return r.json().then(function (d) { return {ok:r.ok,d:d}; }); });

        LiveProgress.runAiStages(stages, request).then(function (res) {
            if (!res.ok) throw new Error(res.d.message || 'Fehler');
            var d = res.d;
            if (d.title) document.getElementById('title').value = d.title;
            if (d.description) document.getElementById('description').value = d.description;
            if (d.event_type) document.getElementById('event_type').value = d.event_type;
            if (d.location_name) document.getElementById('location_name').value = d.location_name;
            if (d.city) document.getElementById('city').value = d.city;
            if (d.starts_on) document.getElementById('starts_on').value = d.starts_on;
            if (d.starts_at_time) document.getElementById('starts_at_time').value = d.starts_at_time;
            if (d.source_url) document.getElementById('source_url').value = d.source_url;
            LiveProgress.log('Titel: ' + (d.title || '—'), 'ok');
            if (d.needs_review) LiveProgress.log('Bitte Datum und Ort manuell prüfen', 'warn');
            LiveProgress.finish({
                ok: true,
                title: 'Event-Entwurf bereit',
                message: (d.notes || 'Entwurf eingefügt.') + (d.needs_review ? ' Bitte Datum/Ort prüfen.' : ''),
                actionsHtml: '<button type="button" class="btn btn--small" data-lp-close>Zum Formular</button>'
            });
            statusEl.hidden = false;
            statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--ok';
            statusEl.textContent = (d.notes || 'Entwurf eingefügt.') + (d.needs_review ? ' Bitte Datum/Ort prüfen.' : '');
        }).catch(function (e) {
            if (window.LiveProgress) {
                LiveProgress.finish({ ok: false, message: e.message || 'Fehler' });
            }
            statusEl.hidden = false;
            statusEl.className = 'xp-ai-studio__status xp-ai-studio__status--error';
            statusEl.textContent = e.message || 'Fehler';
        }).finally(function () { btn.disabled = false; });
    });
})();
</script>
