/**
 * CV Control Room — AJAX wizard with live design/color + library
 */
(function () {
    'use strict';

    var root = document.getElementById('cvStudio');
    if (!root) return;

    var boot;
    try { boot = JSON.parse(root.getAttribute('data-boot') || '{}'); }
    catch (e) { console.error(e); return; }

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var urls = boot.urls || {};
    var steps = boot.steps || [];
    var templates = boot.templates || {};
    var colors = boot.colors || { '#0E4971': 'Nachtblau' };
    var EST_TOTAL_SEC = 150; // ~2.5 minutes for 5 AI sections
    var LEAVE_MSG = 'Die KI-Generierung läuft noch (ca. 2–3 Minuten). Wenn Sie die Seite aktualisieren oder verlassen, geht der aktuelle Fortschritt verloren.';

    var state = {
        step: 1,
        cv: null,
        template: 'classic',
        color: '#0E4971',
        notes: '',
        generating: false,
        saveTimer: null,
        liveTimer: null,
        etaTimer: null,
        genStartedAt: 0,
        genStepIndex: 0,
        genStepTotal: 5,
    };

    var profileForm = root.querySelector('[data-cvs-form-profile]');
    var reviewForm = root.querySelector('[data-cvs-form-review]');
    var toastEl = root.querySelector('[data-cvs-toast]');
    var feedEl = root.querySelector('[data-cvs-feed]');
    var genStepsEl = root.querySelector('[data-cvs-gen-steps]');
    var barEl = root.querySelector('[data-cvs-bar]');
    var pctEl = root.querySelector('[data-cvs-pct]');
    var preview = root.querySelector('[data-cvs-preview]');
    var livePreview = root.querySelector('[data-cvs-live-preview]');

    function url(key, id) {
        return String(urls[key] || '').replace('__ID__', id != null ? id : (state.cv && state.cv.id) || '');
    }

    function toast(msg, kind) {
        if (!toastEl) return;
        toastEl.hidden = false;
        toastEl.textContent = msg;
        toastEl.className = 'cvs__toast' + (kind ? ' is-' + kind : '');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toastEl.hidden = true; }, 3200);
    }

    function linesToText(v) { return Array.isArray(v) ? v.join('\n') : (v == null ? '' : String(v)); }
    function textToLines(v) {
        return String(v || '').split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    }
    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fillForm(form, data) {
        if (!form || !data) return;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || el.type === 'button' || el.type === 'submit') return;
            if (el.name === 'notes') { el.value = state.notes || ''; return; }
            if (el.name === 'title') {
                el.value = (state.cv && state.cv.title) || ('Lebenslauf — ' + ((boot.expert && boot.expert.display_name) || ''));
                return;
            }
            var val = data[el.name];
            el.value = Array.isArray(val) ? linesToText(val) : (val == null ? '' : String(val));
        });
    }

    function readForm(form) {
        var out = {};
        if (!form) return out;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || el.type === 'button' || el.type === 'submit') return;
            out[el.name] = el.value;
        });
        return out;
    }

    function payloadFromForms() {
        var p = Object.assign({}, boot.seed || {}, readForm(profileForm));
        if (state.cv && state.cv.payload) p = Object.assign({}, state.cv.payload, p);
        var review = readForm(reviewForm);
        ['summary', 'services', 'experience', 'education', 'skills', 'languages', 'title'].forEach(function (k) {
            if (review[k] != null && String(review[k]).length) p[k] = review[k];
        });
        state.notes = p.notes || state.notes || '';
        delete p.notes;
        ['experience_areas', 'diplomas', 'criteria', 'experience', 'education', 'skills', 'languages'].forEach(function (k) {
            if (typeof p[k] === 'string') p[k] = textToLines(p[k]);
        });
        if (boot.seed && boot.seed.photo_path) p.photo_path = boot.seed.photo_path;
        p.accent_color = state.color || '#0E4971';
        return p;
    }

    function setStep(n) {
        state.step = n;
        root.querySelectorAll('[data-cvs-panel]').forEach(function (panel) {
            var id = panel.getAttribute('data-cvs-panel');
            var on = String(id) === String(n);
            panel.hidden = !on;
            panel.classList.toggle('is-active', on);
        });
        root.querySelectorAll('[data-step-nav]').forEach(function (li) {
            var id = li.getAttribute('data-step-nav');
            li.classList.toggle('is-active', String(id) === String(n));
            if (id !== 'library' && !isNaN(parseInt(id, 10)) && !isNaN(parseInt(n, 10))) {
                li.classList.toggle('is-done', parseInt(id, 10) < parseInt(n, 10));
            }
        });
        if (n === 2) scheduleLivePreview(true);
        if (n === 4) refreshPreview();
        if (n === 'library') renderLibrary();
    }

    function renderTemplates() {
        var box = root.querySelector('[data-cvs-templates]');
        if (!box) return;
        box.innerHTML = '';
        Object.keys(templates).forEach(function (key) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cvs__tpl' + (state.template === key ? ' is-on' : '');
            btn.innerHTML = '<strong>' + escapeHtml(templates[key]) + '</strong><span>' + templateHint(key) + '</span>';
            btn.addEventListener('click', function () {
                state.template = key;
                renderTemplates();
                scheduleLivePreview(true);
                if (state.cv) saveDesignQuiet();
            });
            box.appendChild(btn);
        });
    }

    function templateHint(key) {
        if (key === 'modern') return 'Zweispaltig, klar';
        if (key === 'executive') return 'Editorial, seriös';
        return 'Klassisch, printfreundlich';
    }

    function renderColors() {
        [root.querySelector('[data-cvs-colors]'), root.querySelector('[data-cvs-colors-review]')].forEach(function (box) {
            if (!box) return;
            box.innerHTML = '';
            Object.keys(colors).forEach(function (hex) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cvs__swatch' + (state.color === hex ? ' is-on' : '');
                btn.title = colors[hex] || hex;
                btn.style.background = hex;
                btn.setAttribute('aria-label', colors[hex] || hex);
                btn.addEventListener('click', function () {
                    state.color = hex;
                    renderColors();
                    scheduleLivePreview(true);
                    if (state.step === 4 && state.cv) {
                        saveReview().catch(function () {});
                    } else if (state.cv) {
                        saveDesignQuiet();
                    }
                });
                box.appendChild(btn);
            });
        });
    }

    function upsertCvInList(cv) {
        boot.cvs = boot.cvs || [];
        var found = false;
        boot.cvs = boot.cvs.map(function (c) {
            if (c.id === cv.id) { found = true; return cv; }
            return c;
        });
        if (!found) boot.cvs.unshift(cv);
        renderLibrary();
    }

    function renderLibrary() {
        var box = root.querySelector('[data-cvs-library]');
        if (!box) return;
        var list = boot.cvs || [];
        if (!list.length) {
            box.innerHTML = '<div class="cvs__library-empty"><strong>Noch keine CVs</strong><p>Erzeugen Sie Ihren ersten Lebenslauf über die Schritte links.</p></div>';
            return;
        }
        box.innerHTML = list.map(function (cv) {
            var accent = (cv.payload && cv.payload.accent_color) || '#0E4971';
            var active = state.cv && state.cv.id === cv.id ? ' is-on' : '';
            return (
                '<article class="cvs__card' + active + '">' +
                '<div class="cvs__card-swatch" style="background:' + escapeHtml(accent) + '"></div>' +
                '<div class="cvs__card-body">' +
                '<strong>' + escapeHtml(cv.title || 'Lebenslauf') + '</strong>' +
                '<span>' + escapeHtml((templates[cv.template_key] || cv.template_key) + ' · ' + (cv.status === 'ready' ? 'Final' : 'Entwurf') + ' · ' + (cv.updated_label || '')) + '</span>' +
                '</div>' +
                '<div class="cvs__card-actions">' +
                '<button type="button" class="btn btn--small" data-load-cv="' + cv.id + '">Öffnen</button>' +
                (cv.pdf_url ? '<a class="btn btn--ghost btn--small" href="' + escapeHtml(cv.pdf_url) + '" target="_blank" rel="noopener">Ansehen</a>' : '') +
                '<button type="button" class="btn btn--ghost btn--small" data-del-cv="' + cv.id + '">Löschen</button>' +
                '</div></article>'
            );
        }).join('');
        box.querySelectorAll('[data-load-cv]').forEach(function (btn) {
            btn.addEventListener('click', function () { loadCv(parseInt(btn.getAttribute('data-load-cv'), 10)); });
        });
        box.querySelectorAll('[data-del-cv]').forEach(function (btn) {
            btn.addEventListener('click', function () { deleteCv(parseInt(btn.getAttribute('data-del-cv'), 10)); });
        });
    }

    async function api(method, endpoint, body) {
        var res = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined,
        });
        var data = null;
        try { data = await res.json(); } catch (e) { data = null; }
        if (!res.ok) {
            var msg = (data && (data.message || (data.errors && Object.values(data.errors)[0]))) || ('Fehler ' + res.status);
            throw new Error(typeof msg === 'string' ? msg : 'Anfrage fehlgeschlagen');
        }
        return data;
    }

    async function livePreviewHtml() {
        if (!livePreview || !urls.live_preview) return;
        var payload = payloadFromForms();
        var body = Object.assign({}, payload, { template_key: state.template, accent_color: state.color });
        var res = await fetch(urls.live_preview, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        if (!res.ok) throw new Error('Live-Vorschau fehlgeschlagen');
        var html = await res.text();
        livePreview.srcdoc = html;
    }

    function scheduleLivePreview(immediate) {
        clearTimeout(state.liveTimer);
        state.liveTimer = setTimeout(function () {
            livePreviewHtml().catch(function () {});
        }, immediate ? 80 : 450);
    }

    function logFeed(text, kind) {
        if (!feedEl) return;
        var row = document.createElement('div');
        row.className = 'cvs__feed-row' + (kind ? ' is-' + kind : '');
        row.innerHTML = '<time>' + new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + '</time><span>' + escapeHtml(text) + '</span>';
        feedEl.appendChild(row);
        feedEl.scrollTop = feedEl.scrollHeight;
    }

    function setProgress(pct, title, sub) {
        if (pctEl) pctEl.textContent = Math.round(pct) + '%';
        if (barEl) barEl.style.width = Math.max(0, Math.min(100, pct)) + '%';
        var t = root.querySelector('[data-cvs-progress-title]');
        var s = root.querySelector('[data-cvs-progress-sub]');
        if (t && title) t.textContent = title;
        if (s && sub) s.textContent = sub;
    }

    function formatEta(sec) {
        sec = Math.max(0, Math.round(sec));
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    function updateEtaUi() {
        var badge = root.querySelector('[data-cvs-eta-badge]');
        var text = root.querySelector('[data-cvs-eta-text]');
        if (!state.generating) {
            if (badge) badge.textContent = 'Fertig';
            if (text) text.textContent = 'Generierung abgeschlossen. Sie können fortfahren.';
            return;
        }
        var elapsed = (Date.now() - state.genStartedAt) / 1000;
        var perStep = EST_TOTAL_SEC / Math.max(1, state.genStepTotal);
        var doneSteps = state.genStepIndex;
        var remaining = Math.max(15, (state.genStepTotal - doneSteps) * perStep - (elapsed % perStep));
        // Blend wall-clock estimate with step-based estimate
        var wallRemain = Math.max(10, EST_TOTAL_SEC - elapsed);
        remaining = Math.round((remaining + wallRemain) / 2);
        if (badge) badge.textContent = '~' + formatEta(remaining);
        if (text) {
            text.textContent = 'Geschätzte Restzeit: ca. ' + formatEta(remaining) +
                ' · Schritt ' + Math.min(doneSteps + 1, state.genStepTotal) + ' von ' + state.genStepTotal +
                ' · Seite nicht aktualisieren oder schliessen.';
        }
    }

    function startEtaTicker() {
        stopEtaTicker();
        state.genStartedAt = Date.now();
        updateEtaUi();
        state.etaTimer = setInterval(updateEtaUi, 1000);
    }

    function stopEtaTicker() {
        if (state.etaTimer) clearInterval(state.etaTimer);
        state.etaTimer = null;
    }

    function confirmLeaveIfGenerating() {
        if (!state.generating) return true;
        return window.confirm(
            LEAVE_MSG + '\n\nTrotzdem fortfahren? Die laufende KI-Generierung wird abgebrochen.'
        );
    }

    function bindLeaveGuard() {
        window.addEventListener('beforeunload', function (e) {
            if (!state.generating) return;
            e.preventDefault();
            e.returnValue = LEAVE_MSG;
            return LEAVE_MSG;
        });
    }

    function buildGenStepsUi() {
        if (!genStepsEl) return;
        genStepsEl.innerHTML = steps.map(function (st) {
            return '<li data-gen="' + st.id + '"><i></i><div><strong>' + escapeHtml(st.label) + '</strong><em>—</em></div></li>';
        }).join('');
    }

    function markGen(id, status, detail) {
        var li = genStepsEl && genStepsEl.querySelector('[data-gen="' + id + '"]');
        if (!li) return;
        li.classList.remove('is-active', 'is-done', 'is-error');
        if (status) li.classList.add('is-' + status);
        var em = li.querySelector('em');
        if (em && detail) em.textContent = detail;
    }

    async function ensureDraft() {
        var payload = payloadFromForms();
        var body = Object.assign({}, payload, {
            template_key: state.template,
            accent_color: state.color,
            title: (readForm(reviewForm).title) || ('Lebenslauf — ' + ((boot.expert && boot.expert.display_name) || '')),
            notes: state.notes,
        });
        if (state.cv && state.cv.id) {
            var updated = await api('PUT', url('update', state.cv.id), body);
            state.cv = updated.cv;
            upsertCvInList(state.cv);
            return state.cv;
        }
        var created = await api('POST', urls.store, body);
        state.cv = created.cv;
        upsertCvInList(state.cv);
        return state.cv;
    }

    async function saveDesignQuiet() {
        try { await ensureDraft(); } catch (e) {}
    }

    async function runGeneration() {
        if (state.generating) return;
        var okStart = window.confirm(
            'KI-Generierung starten?\n\n' +
            'Dauer: ca. 2–3 Minuten (5 Abschnitte nacheinander).\n' +
            'Bitte Seite nicht aktualisieren und Tab nicht schliessen.\n\n' +
            'Warum so lange? Jeder Abschnitt braucht einen eigenen KI-Aufruf — so bleibt die Qualität hoch.'
        );
        if (!okStart) return;

        state.generating = true;
        state.genStepTotal = steps.length || 5;
        state.genStepIndex = 0;
        root.classList.add('is-generating');
        setStep(3);
        feedEl.innerHTML = '';
        buildGenStepsUi();
        root.querySelector('[data-cvs-gen-actions]').hidden = true;
        startEtaTicker();
        setProgress(4, 'Entwurf wird vorbereitet…', 'Profil, Design & Farbe speichern · Gesamt ca. 2,5 Min.');
        logFeed('Hinweis: Gesamtdauer ca. 2–3 Minuten. Bitte warten.', 'working');
        logFeed('Profil und Design werden gespeichert…', 'working');
        try {
            await ensureDraft();
            logFeed('Entwurf #' + state.cv.id + ' bereit (' + (templates[state.template] || state.template) + ').', 'ok');
            var total = steps.length;
            for (var i = 0; i < total; i++) {
                state.genStepIndex = i;
                updateEtaUi();
                var st = steps[i];
                setProgress(
                    8 + ((i / total) * 82),
                    st.label + ' wird geschrieben…',
                    'Schritt ' + (i + 1) + ' von ' + total + ' · Restzeit ca. ' + formatEta(((total - i) / total) * EST_TOTAL_SEC)
                );
                markGen(st.id, 'active', 'KI arbeitet… bitte warten');
                logFeed('Erzeuge: ' + st.label + ' (Abschnitt ' + (i + 1) + '/' + total + ')', 'working');
                var res = await api('POST', url('generate', state.cv.id), { field: st.id, notes: state.notes || '' });
                state.cv = res.cv;
                upsertCvInList(state.cv);
                state.genStepIndex = i + 1;
                updateEtaUi();
                markGen(st.id, 'done', (res.preview || 'Fertig').slice(0, 90) + (res.preview && res.preview.length > 90 ? '…' : ''));
                logFeed(st.label + ' fertig.', 'ok');
                fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            }
            setProgress(100, 'Alles generiert', 'Weiter zum Feinschliff');
            logFeed('Alle Abschnitte erzeugt. Danke fürs Warten.', 'ok');
            root.querySelector('[data-cvs-gen-actions]').hidden = false;
            toast('CV-Inhalt erzeugt', 'ok');
        } catch (err) {
            logFeed(err.message || 'Fehler', 'error');
            setProgress(100, 'Unterbrochen', err.message || 'Bitte erneut versuchen');
            toast(err.message || 'Generierung fehlgeschlagen', 'error');
            root.querySelector('[data-cvs-gen-actions]').hidden = false;
        } finally {
            state.generating = false;
            root.classList.remove('is-generating');
            stopEtaTicker();
            updateEtaUi();
            var badge = root.querySelector('[data-cvs-eta-badge]');
            var text = root.querySelector('[data-cvs-eta-text]');
            if (badge) badge.textContent = 'Fertig';
            if (text) text.textContent = 'Generierung beendet. Seite darf wieder gewechselt werden.';
        }
    }

    async function skipAi() {
        if (state.generating) {
            if (!confirmLeaveIfGenerating()) return;
            state.generating = false;
            stopEtaTicker();
            root.classList.remove('is-generating');
        }
        var ok = window.confirm(
            'KI überspringen?\n\n' +
            'Es wird nur ein Entwurf aus Ihren Profilangaben erstellt — ohne KI-Texte für Zusammenfassung, Erfahrung usw.\n' +
            'Sie können die Felder danach manuell ausfüllen.'
        );
        if (!ok) return;
        try {
            await ensureDraft();
            fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            setStep(4);
            toast('Entwurf ohne KI erstellt', 'ok');
        } catch (err) { toast(err.message || 'Fehler', 'error'); }
    }

    function refreshPreview() {
        if (!preview || !state.cv) return;
        preview.src = url('preview', state.cv.id) + '?t=' + Date.now();
    }

    async function saveReview() {
        if (!state.cv) await ensureDraft();
        var payload = payloadFromForms();
        var body = Object.assign({}, payload, {
            template_key: state.template,
            accent_color: state.color,
            title: readForm(reviewForm).title || state.cv.title,
        });
        var res = await api('PUT', url('update', state.cv.id), body);
        state.cv = res.cv;
        upsertCvInList(state.cv);
        refreshPreview();
        toast('Gespeichert', 'ok');
    }

    async function finalize() {
        try {
            await saveReview();
            var res = await api('POST', url('finalize', state.cv.id), {});
            state.cv = res.cv;
            upsertCvInList(state.cv);
            var open = root.querySelector('[data-cvs-open-final]');
            if (open) open.href = state.cv.pdf_url || url('preview', state.cv.id);
            var msg = root.querySelector('[data-cvs-done-msg]');
            if (msg) msg.textContent = res.message || 'Finalisierte Version steht bereit.';
            setStep(5);
            toast('Finalisiert', 'ok');
        } catch (err) { toast(err.message || 'Finalisieren fehlgeschlagen', 'error'); }
    }

    async function loadCv(id) {
        try {
            var res = await api('GET', url('show', id));
            state.cv = res.cv;
            state.template = state.cv.template_key || 'classic';
            state.color = (state.cv.payload && state.cv.payload.accent_color) || '#0E4971';
            fillForm(profileForm, state.cv.payload || {});
            fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            renderTemplates();
            renderColors();
            renderLibrary();
            setStep(state.cv.status === 'ready' ? 5 : 4);
            if (state.cv.status === 'ready') {
                var open = root.querySelector('[data-cvs-open-final]');
                if (open) open.href = state.cv.pdf_url || url('preview', state.cv.id);
            }
            refreshPreview();
            toast('CV geladen', 'ok');
        } catch (err) { toast(err.message || 'Laden fehlgeschlagen', 'error'); }
    }

    async function deleteCv(id) {
        if (!confirm('Diesen CV wirklich löschen?')) return;
        try {
            await api('DELETE', url('destroy', id));
            boot.cvs = (boot.cvs || []).filter(function (c) { return c.id !== id; });
            if (state.cv && state.cv.id === id) state.cv = null;
            renderLibrary();
            toast('CV gelöscht', 'ok');
        } catch (err) { toast(err.message || 'Löschen fehlgeschlagen', 'error'); }
    }

    async function suggestTemplate() {
        var btn = root.querySelector('[data-cvs-suggest-template]');
        var label = root.querySelector('[data-cvs-suggest-label]');
        if (btn) btn.disabled = true;
        if (label) label.textContent = 'KI wählt… (~30 Sek.)';
        toast('KI analysiert Ihr Profil für die passende Vorlage… bitte kurz warten.', 'ok');
        try {
            var res = await api('POST', urls.suggest_template, { payload: payloadFromForms() });
            state.template = res.template_key;
            renderTemplates();
            scheduleLivePreview(true);
            toast('Vorschlag: ' + (res.template_label || res.template_key), 'ok');
        } catch (err) {
            toast(err.message || 'Vorlage-Vorschlag fehlgeschlagen', 'error');
        } finally {
            if (btn) btn.disabled = false;
            if (label) label.textContent = 'KI-Vorlage';
        }
    }

    function restart() {
        if (!confirmLeaveIfGenerating()) return;
        state.generating = false;
        stopEtaTicker();
        root.classList.remove('is-generating');
        state.cv = null;
        state.template = 'classic';
        state.color = '#0E4971';
        state.notes = '';
        fillForm(profileForm, boot.seed || {});
        if (reviewForm) reviewForm.reset();
        renderTemplates();
        renderColors();
        renderLibrary();
        setStep(1);
        toast('Neuer CV gestartet');
    }

    root.querySelectorAll('[data-cvs-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirmLeaveIfGenerating()) return;
            if (btn.getAttribute('data-cvs-next') === '2') state.notes = (readForm(profileForm).notes || '');
            setStep(btn.getAttribute('data-cvs-next') === 'library' ? 'library' : parseInt(btn.getAttribute('data-cvs-next'), 10));
        });
    });
    root.querySelectorAll('[data-cvs-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirmLeaveIfGenerating()) return;
            setStep(parseInt(btn.getAttribute('data-cvs-prev'), 10));
        });
    });
    root.querySelectorAll('[data-step-nav]').forEach(function (li) {
        li.addEventListener('click', function () {
            if (!confirmLeaveIfGenerating()) return;
            var id = li.getAttribute('data-step-nav');
            if (id === 'library') { setStep('library'); return; }
            var n = parseInt(id, 10);
            if (n === 3 && !state.cv && !state.generating) return;
            if (n >= 4 && !state.cv) return;
            setStep(n);
        });
    });
    root.querySelectorAll('[data-cvs-open-library]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirmLeaveIfGenerating()) return;
            setStep('library');
        });
    });
    root.querySelectorAll('[data-cvs-restart]').forEach(function (btn) {
        btn.addEventListener('click', restart);
    });
    bindLeaveGuard();

    var startBtn = root.querySelector('[data-cvs-start-generate]');
    if (startBtn) startBtn.addEventListener('click', runGeneration);
    var skipBtn = root.querySelector('[data-cvs-skip-ai]');
    if (skipBtn) skipBtn.addEventListener('click', skipAi);
    var suggestBtn = root.querySelector('[data-cvs-suggest-template]');
    if (suggestBtn) suggestBtn.addEventListener('click', suggestTemplate);
    root.querySelectorAll('[data-cvs-finalize]').forEach(function (btn) {
        btn.addEventListener('click', finalize);
    });
    var saveBtn = root.querySelector('[data-cvs-save-review]');
    if (saveBtn) saveBtn.addEventListener('click', function () { saveReview().catch(function (err) { toast(err.message, 'error'); }); });
    var refreshBtn = root.querySelector('[data-cvs-refresh-preview]');
    if (refreshBtn) refreshBtn.addEventListener('click', function () { saveReview().catch(function (err) { toast(err.message, 'error'); }); });
    var liveRefresh = root.querySelector('[data-cvs-live-refresh]');
    if (liveRefresh) liveRefresh.addEventListener('click', function () { scheduleLivePreview(true); });

    if (profileForm) profileForm.addEventListener('input', function () { if (state.step === 2) scheduleLivePreview(false); });
    if (reviewForm) {
        reviewForm.addEventListener('input', function () {
            clearTimeout(state.saveTimer);
            state.saveTimer = setTimeout(function () {
                if (!state.cv || state.step !== 4) return;
                saveReview().catch(function () {});
            }, 1400);
        });
    }

    fillForm(profileForm, boot.seed || {});
    if (boot.seed && boot.seed.accent_color) state.color = boot.seed.accent_color;
    renderTemplates();
    renderColors();
    renderLibrary();
    buildGenStepsUi();

    if (boot.initial_cv_id) loadCv(boot.initial_cv_id);
    else setStep(1);
})();
