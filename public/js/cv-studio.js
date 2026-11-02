/**
 * CV Studio — 3-step flow, single-shot AI (~under 1 min)
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
    var templates = boot.templates || {};
    var templateHints = boot.template_hints || {};
    var colors = boot.colors || { '#0E4971': 'Nachtblau' };
    var EST_TOTAL_SEC = 45;
    var LEAVE_MSG = 'Die KI-Generierung läuft noch. Wenn Sie die Seite aktualisieren oder verlassen, geht der aktuelle Fortschritt verloren.';

    var state = {
        step: 1,
        cv: null,
        template: 'classic',
        color: '#0E4971',
        layout: { style: 'centered', density: 'normal', type_mood: 'sans' },
        notes: '',
        generating: false,
        inventing: false,
        saveTimer: null,
        liveTimer: null,
        etaTimer: null,
        genStartedAt: 0,
        zoomLive: 0.7,
        zoomReview: 0.7,
    };

    var profileForm = root.querySelector('[data-cvs-form-profile]');
    var reviewForm = root.querySelector('[data-cvs-form-review]');
    var toastEl = root.querySelector('[data-cvs-toast]');
    var barEl = root.querySelector('[data-cvs-bar]');
    var pctEl = root.querySelector('[data-cvs-pct]');
    var preview = root.querySelector('[data-cvs-preview]');
    var livePreview = root.querySelector('[data-cvs-live-preview]');
    var overlay = root.querySelector('[data-cvs-gen-overlay]');
    var designOverlay = root.querySelector('[data-cvs-design-overlay]');
    var resumeBanner = root.querySelector('[data-cvs-resume]');

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
        p.layout = state.layout || { style: 'centered', density: 'normal', type_mood: 'sans' };
        return p;
    }

    function setStep(n, opts) {
        opts = opts || {};
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
        if (n === 2) {
            applyZoom('live');
            scheduleLivePreview(true);
        }
        if (n === 3) {
            applyZoom('review');
            refreshPreview();
        }
        if (n === 'library') renderLibrary();
        if (!opts.skipSave && state.cv && (n === 1 || n === 2 || n === 3)) {
            persistDraftQuiet(n);
        }
    }

    function applyZoom(which) {
        var scale = which === 'review' ? state.zoomReview : state.zoomLive;
        var frame = root.querySelector(which === 'review' ? '[data-cvs-review-frame]' : '[data-cvs-live-frame]');
        var iframe = which === 'review' ? preview : livePreview;
        var label = root.querySelector(which === 'review' ? '[data-cvs-zoom-label-review]' : '[data-cvs-zoom-label]');
        if (label) label.textContent = Math.round(scale * 100) + '%';
        if (!frame || !iframe) return;
        frame.style.setProperty('--cvs-zoom', String(scale));
        iframe.style.width = (100 / scale) + '%';
        iframe.style.height = (100 / scale) + '%';
        iframe.style.transform = 'scale(' + scale + ')';
        iframe.style.transformOrigin = 'top left';
    }

    function bumpZoom(which, delta) {
        if (which === 'review') {
            state.zoomReview = Math.max(0.45, Math.min(1, +(state.zoomReview + delta).toFixed(2)));
        } else {
            state.zoomLive = Math.max(0.45, Math.min(1, +(state.zoomLive + delta).toFixed(2)));
        }
        applyZoom(which);
    }

    function templateHint(key) {
        return templateHints[key] || '';
    }

    function onTemplatePicked(key) {
        state.template = key;
        renderTemplates();
        renderLayoutChips();
        scheduleLivePreview(true);
        if (state.step === 3 && state.cv) {
            saveReview().then(function () { toast('Vorlage gespeichert', 'ok'); }).catch(function (err) {
                toast(err.message || 'Speichern fehlgeschlagen', 'error');
            });
        } else if (state.cv) {
            saveDesignQuiet();
        }
    }

    function renderTemplates() {
        [root.querySelector('[data-cvs-templates]'), root.querySelector('[data-cvs-templates-review]')].forEach(function (box) {
            if (!box) return;
            box.innerHTML = '';
            Object.keys(templates).forEach(function (key) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cvs__tpl' + (state.template === key ? ' is-on' : '') + (key === 'ai_design' ? ' cvs__tpl--ai' : '');
                btn.innerHTML = '<strong>' + escapeHtml(templates[key]) + '</strong><span>' + escapeHtml(templateHint(key)) + '</span>';
                btn.addEventListener('click', function () { onTemplatePicked(key); });
                box.appendChild(btn);
            });
        });
        renderLayoutChips();
    }

    function renderLayoutChips() {
        var wrap = root.querySelector('[data-cvs-layout-chips]');
        var box = root.querySelector('[data-cvs-layout-styles]');
        if (!wrap || !box) return;
        var show = state.template === 'ai_design';
        wrap.hidden = !show;
        if (!show) return;
        var styles = [
            { id: 'sidebar_left', label: 'Sidebar links' },
            { id: 'sidebar_right', label: 'Sidebar rechts' },
            { id: 'banner', label: 'Banner' },
            { id: 'centered', label: 'Zentriert' },
            { id: 'split', label: 'Zweispalter' },
        ];
        var current = (state.layout && state.layout.style) || 'centered';
        box.innerHTML = styles.map(function (s) {
            return '<button type="button" class="cvs__chip' + (current === s.id ? ' is-on' : '') + '" data-layout-style="' + s.id + '">' + escapeHtml(s.label) + '</button>';
        }).join('');
        box.querySelectorAll('[data-layout-style]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.layout = Object.assign({}, state.layout || {}, { style: btn.getAttribute('data-layout-style') });
                state.template = 'ai_design';
                renderTemplates();
                scheduleLivePreview(true);
                if (state.cv) {
                    (state.step === 3 ? saveReview() : ensureDraft()).then(function () {
                        if (state.step === 3) refreshPreview();
                        toast('Layout gespeichert', 'ok');
                    }).catch(function (err) { toast(err.message || 'Fehler', 'error'); });
                }
            });
        });
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
                    if (state.step === 3 && state.cv) {
                        saveReview().then(function () { toast('Farbe gespeichert', 'ok'); }).catch(function () {});
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

    function previewHref(cvId) {
        return url('preview', cvId) + '?t=' + Date.now();
    }

    function pdfHref(cvId) {
        return url('pdf', cvId) + '?t=' + Date.now();
    }

    function renderLibrary() {
        var box = root.querySelector('[data-cvs-library]');
        if (!box) return;
        var list = boot.cvs || [];
        if (!list.length) {
            box.innerHTML = '<div class="cvs__library-empty"><strong>Noch keine CVs</strong><p>Erzeugen Sie Ihren ersten Lebenslauf in 3 Schritten.</p></div>';
            return;
        }
        box.innerHTML = list.map(function (cv) {
            var accent = (cv.payload && cv.payload.accent_color) || '#0E4971';
            var active = state.cv && state.cv.id === cv.id ? ' is-on' : '';
            var status = cv.status === 'ready' ? 'Final' : ('Entwurf · Schritt ' + (cv.studio_step || 1));
            return (
                '<article class="cvs__card' + active + '">' +
                '<div class="cvs__card-swatch" style="background:' + escapeHtml(accent) + '"></div>' +
                '<div class="cvs__card-body">' +
                '<strong>' + escapeHtml(cv.title || 'Lebenslauf') + '</strong>' +
                '<span>' + escapeHtml((templates[cv.template_key] || cv.template_key) + ' · ' + status + ' · ' + (cv.updated_label || '')) + '</span>' +
                '</div>' +
                '<div class="cvs__card-actions">' +
                '<button type="button" class="btn btn--small" data-load-cv="' + cv.id + '">' + (cv.status === 'draft' ? 'Fortsetzen' : 'Bearbeiten') + '</button>' +
                '<a class="btn btn--ghost btn--small" href="' + escapeHtml(previewHref(cv.id)) + '" target="_blank" rel="noopener">Vorschau</a>' +
                '<a class="btn btn--ghost btn--small" href="' + escapeHtml(pdfHref(cv.id)) + '" target="_blank" rel="noopener">PDF</a>' +
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
        var body = Object.assign({}, payload, { template_key: state.template, accent_color: state.color, layout: state.layout });
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
        livePreview.srcdoc = await res.text();
        applyZoom('live');
    }

    function scheduleLivePreview(immediate) {
        clearTimeout(state.liveTimer);
        state.liveTimer = setTimeout(function () {
            livePreviewHtml().catch(function () {});
        }, immediate ? 80 : 450);
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
            if (text) text.textContent = 'Generierung abgeschlossen.';
            return;
        }
        var elapsed = (Date.now() - state.genStartedAt) / 1000;
        var remaining = Math.max(5, EST_TOTAL_SEC - elapsed);
        if (badge) badge.textContent = '~' + formatEta(remaining);
        if (text) {
            text.textContent = 'Noch ca. ' + formatEta(remaining) + ' · Seite nicht aktualisieren oder schliessen.';
        }
        var pct = Math.min(92, 8 + (elapsed / EST_TOTAL_SEC) * 84);
        setProgress(pct, 'KI schreibt Ihren Lebenslauf…', 'Ein Durchgang · bitte warten');
    }

    function startEtaTicker() {
        stopEtaTicker();
        state.genStartedAt = Date.now();
        updateEtaUi();
        state.etaTimer = setInterval(updateEtaUi, 500);
    }

    function stopEtaTicker() {
        if (state.etaTimer) clearInterval(state.etaTimer);
        state.etaTimer = null;
    }

    function confirmLeaveIfGenerating() {
        if (!state.generating) return true;
        return window.confirm(LEAVE_MSG + '\n\nTrotzdem fortfahren? Die laufende KI-Generierung wird abgebrochen.');
    }

    function bindLeaveGuard() {
        window.addEventListener('beforeunload', function (e) {
            if (!state.generating) return;
            e.preventDefault();
            e.returnValue = LEAVE_MSG;
            return LEAVE_MSG;
        });
    }

    async function ensureDraft(stepOverride) {
        var payload = payloadFromForms();
        var body = Object.assign({}, payload, {
            template_key: state.template,
            accent_color: state.color,
            layout: state.layout,
            studio_step: stepOverride != null ? stepOverride : (typeof state.step === 'number' ? state.step : 1),
            title: (readForm(reviewForm).title) || ('Lebenslauf — ' + ((boot.expert && boot.expert.display_name) || '')),
        });
        if (state.cv && state.cv.id) {
            var updated = await api('PUT', url('update', state.cv.id), body);
            state.cv = updated.cv;
            upsertCvInList(state.cv);
            boot.resume_cv = state.cv.status === 'draft' ? state.cv : boot.resume_cv;
            return state.cv;
        }
        var created = await api('POST', urls.store, body);
        state.cv = created.cv;
        upsertCvInList(state.cv);
        boot.resume_cv = state.cv;
        return state.cv;
    }

    function saveDesignQuiet() {
        ensureDraft().catch(function () {});
    }

    function persistDraftQuiet(step) {
        clearTimeout(state.saveTimer);
        state.saveTimer = setTimeout(function () {
            ensureDraft(step).catch(function () {});
        }, 450);
    }

    async function saveDraftExplicit() {
        try {
            await ensureDraft(typeof state.step === 'number' ? state.step : 3);
            toast('Entwurf gespeichert — Sie können später fortsetzen', 'ok');
            showResumeBanner();
        } catch (err) {
            toast(err.message || 'Entwurf speichern fehlgeschlagen', 'error');
        }
    }

    function showResumeBanner() {
        if (!resumeBanner || !boot.resume_cv || (state.cv && state.cv.id === boot.resume_cv.id && state.step !== 1)) {
            if (resumeBanner && state.cv) resumeBanner.hidden = true;
            return;
        }
        var draft = boot.resume_cv;
        if (!draft || draft.status === 'ready') {
            resumeBanner.hidden = true;
            return;
        }
        var text = root.querySelector('[data-cvs-resume-text]');
        if (text) {
            text.textContent = (draft.title || 'Lebenslauf') + ' · Schritt ' + (draft.studio_step || 1) +
                ' · zuletzt ' + (draft.updated_label || '');
        }
        resumeBanner.hidden = false;
    }

    async function runGeneration() {
        if (state.generating) return;

        state.generating = true;
        root.classList.add('is-generating');
        if (overlay) overlay.hidden = false;
        setStep(2);
        startEtaTicker();
        setProgress(6, 'Entwurf wird vorbereitet…', 'Profil & Design speichern');

        try {
            await ensureDraft();
            setProgress(18, 'KI erzeugt den gesamten Lebenslauf…', 'Ein Aufruf · typisch unter 1 Minute');
            var invent = state.template === 'ai_design';
            var res = await api('POST', url('generate', state.cv.id), {
                mode: 'full',
                notes: state.notes || '',
                template_key: state.template,
                invent_design: invent,
                payload: payloadFromForms(),
            });
            state.cv = res.cv;
            state.template = state.cv.template_key || state.template;
            if (state.cv.payload && state.cv.payload.accent_color) state.color = state.cv.payload.accent_color;
            if (state.cv.payload && state.cv.payload.layout) state.layout = state.cv.payload.layout;
            upsertCvInList(state.cv);
            fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            renderTemplates();
            renderColors();
            setProgress(100, 'Fertig', 'Weiter zum Feinschliff');
            toast(res.message || 'Lebenslauf erzeugt', 'ok');
            setStep(3);
            refreshPreview();
        } catch (err) {
            setProgress(100, 'Unterbrochen', err.message || 'Bitte erneut versuchen');
            toast(err.message || 'Generierung fehlgeschlagen', 'error');
        } finally {
            state.generating = false;
            root.classList.remove('is-generating');
            stopEtaTicker();
            if (overlay) overlay.hidden = true;
        }
    }

    async function skipAi() {
        if (state.generating) {
            if (!confirmLeaveIfGenerating()) return;
            state.generating = false;
            stopEtaTicker();
            root.classList.remove('is-generating');
            if (overlay) overlay.hidden = true;
        }
        try {
            await ensureDraft();
            fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            setStep(3);
            refreshPreview();
            toast('Entwurf ohne KI erstellt', 'ok');
        } catch (err) { toast(err.message || 'Fehler', 'error'); }
    }

    async function saveReview() {
        if (!state.cv) await ensureDraft(3);
        var payload = payloadFromForms();
        var body = Object.assign({}, payload, {
            template_key: state.template,
            accent_color: state.color,
            layout: state.layout,
            studio_step: 3,
            title: readForm(reviewForm).title || state.cv.title,
        });
        var res = await api('PUT', url('update', state.cv.id), body);
        state.cv = res.cv;
        upsertCvInList(state.cv);
        refreshPreview();
        return state.cv;
    }

    function scheduleSaveReview() {
        clearTimeout(state.saveTimer);
        state.saveTimer = setTimeout(function () {
            saveReview().catch(function () {});
        }, 700);
    }

    function refreshPreview() {
        if (!preview || !state.cv) return;
        preview.src = previewHref(state.cv.id);
        applyZoom('review');
    }

    async function printCv() {
        if (!state.cv) {
            toast('Bitte zuerst speichern oder erzeugen', 'error');
            return;
        }
        try {
            await saveReview();
            toast('PDF-Ansicht wird geöffnet…', 'ok');
            window.open(pdfHref(state.cv.id), '_blank', 'noopener');
        } catch (err) {
            toast(err.message || 'PDF fehlgeschlagen', 'error');
        }
    }

    async function finalize() {
        try {
            await saveReview();
            var res = await api('POST', url('finalize', state.cv.id));
            state.cv = res.cv;
            upsertCvInList(state.cv);
            if (boot.resume_cv && boot.resume_cv.id === state.cv.id) boot.resume_cv = null;
            showResumeBanner();
            var block = root.querySelector('[data-cvs-done-block]');
            var open = root.querySelector('[data-cvs-open-final]');
            if (block) block.hidden = false;
            if (open) open.href = previewHref(state.cv.id);
            toast(res.message || 'Finalisiert — PDF bereit', 'ok');
            refreshPreview();
        } catch (err) { toast(err.message || 'Finalisieren fehlgeschlagen', 'error'); }
    }

    async function loadCv(id, resumeStep) {
        if (!confirmLeaveIfGenerating()) return;
        try {
            var res = await api('GET', url('show', id));
            state.cv = res.cv;
            state.template = state.cv.template_key || 'classic';
            state.color = (state.cv.payload && state.cv.payload.accent_color) || '#0E4971';
            state.layout = (state.cv.payload && state.cv.payload.layout) || state.layout;
            state.notes = (state.cv.payload && state.cv.payload.notes) || state.notes || '';
            fillForm(profileForm, state.cv.payload || {});
            fillForm(reviewForm, Object.assign({}, state.cv.payload || {}, { title: state.cv.title }));
            renderTemplates();
            renderColors();
            var step = resumeStep != null ? resumeStep : (state.cv.studio_step || 3);
            if (step < 1 || step > 3) step = 3;
            if (resumeBanner) resumeBanner.hidden = true;
            setStep(step, { skipSave: true });
            toast(state.cv.status === 'draft' ? 'Entwurf fortgesetzt (Schritt ' + step + ')' : 'CV geladen', 'ok');
        } catch (err) { toast(err.message || 'Laden fehlgeschlagen', 'error'); }
    }

    async function deleteCv(id) {
        if (!window.confirm('Diesen Lebenslauf löschen?')) return;
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
        if (label) label.textContent = 'Wählt…';
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
            if (label) label.textContent = 'KI wählt Vorlage';
        }
    }

    async function inventDesign() {
        if (!urls.invent_design) {
            toast('KI-Design-URL fehlt — Seite neu laden', 'error');
            return;
        }
        if (state.inventing) return;
        state.inventing = true;
        var btn = root.querySelector('[data-cvs-invent-design]');
        var label = root.querySelector('[data-cvs-invent-label]');
        if (btn) btn.disabled = true;
        if (label) label.textContent = 'Wird geladen…';
        if (designOverlay) designOverlay.hidden = false;
        root.classList.add('is-inventing');
        try {
            var res = await api('POST', urls.invent_design, { payload: payloadFromForms() });
            state.template = 'ai_design';
            if (res.accent_color) state.color = res.accent_color;
            if (res.layout) state.layout = res.layout;
            renderTemplates();
            renderColors();
            await ensureDraft(2);
            scheduleLivePreview(true);
            if (state.step === 3) refreshPreview();
            toast('Design bereit: ' + (res.label || 'KI-Design'), 'ok');
        } catch (err) {
            state.template = 'ai_design';
            state.layout = { style: 'sidebar_left', density: 'normal', type_mood: 'sans' };
            state.color = '#0B6E4F';
            renderTemplates();
            renderColors();
            try { await ensureDraft(2); } catch (e2) {}
            scheduleLivePreview(true);
            toast((err && err.message ? err.message + ' — ' : '') + 'Fallback-Design geladen', 'ok');
        } finally {
            state.inventing = false;
            root.classList.remove('is-inventing');
            if (designOverlay) designOverlay.hidden = true;
            if (btn) btn.disabled = false;
            if (label) label.textContent = 'KI erstellt Design';
        }
    }

    function restart() {
        if (!confirmLeaveIfGenerating()) return;
        state.generating = false;
        stopEtaTicker();
        root.classList.remove('is-generating');
        if (overlay) overlay.hidden = true;
        state.cv = null;
        state.template = 'classic';
        state.color = '#0E4971';
        state.layout = { style: 'centered', density: 'normal', type_mood: 'sans' };
        state.notes = '';
        fillForm(profileForm, boot.seed || {});
        if (reviewForm) reviewForm.reset();
        var done = root.querySelector('[data-cvs-done-block]');
        if (done) done.hidden = true;
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
            setStep(parseInt(btn.getAttribute('data-cvs-next'), 10));
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
            if (n === 3 && !state.cv) return;
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

    var startBtn = root.querySelector('[data-cvs-start-generate]');
    if (startBtn) startBtn.addEventListener('click', runGeneration);
    var skipBtn = root.querySelector('[data-cvs-skip-ai]');
    if (skipBtn) skipBtn.addEventListener('click', skipAi);
    var suggestBtn = root.querySelector('[data-cvs-suggest-template]');
    if (suggestBtn) suggestBtn.addEventListener('click', suggestTemplate);
    var inventBtn = root.querySelector('[data-cvs-invent-design]');
    if (inventBtn) inventBtn.addEventListener('click', inventDesign);
    var liveRefresh = root.querySelector('[data-cvs-live-refresh]');
    if (liveRefresh) liveRefresh.addEventListener('click', function () { scheduleLivePreview(true); });
    root.querySelectorAll('[data-cvs-refresh-preview]').forEach(function (btn) {
        btn.addEventListener('click', function () { refreshPreview(); });
    });
    root.querySelectorAll('[data-cvs-print]').forEach(function (btn) {
        btn.addEventListener('click', function () { printCv(); });
    });
    root.querySelectorAll('[data-cvs-finalize]').forEach(function (btn) {
        btn.addEventListener('click', finalize);
    });
    root.querySelectorAll('[data-cvs-save-draft]').forEach(function (btn) {
        btn.addEventListener('click', function () { saveDraftExplicit(); });
    });
    var saveBtn = root.querySelector('[data-cvs-save-review]');
    if (saveBtn) saveBtn.addEventListener('click', function () {
        saveReview().then(function () {
            toast('Gespeichert — Bibliothek/Vorschau sind aktuell', 'ok');
            refreshPreview();
        }).catch(function (err) {
            toast(err.message || 'Speichern fehlgeschlagen', 'error');
        });
    });
    if (reviewForm) {
        reviewForm.addEventListener('input', scheduleSaveReview);
    }
    if (profileForm) {
        profileForm.addEventListener('change', function () {
            if (state.cv) persistDraftQuiet(1);
        });
    }

    var zoomOut = root.querySelector('[data-cvs-zoom-out]');
    var zoomIn = root.querySelector('[data-cvs-zoom-in]');
    if (zoomOut) zoomOut.addEventListener('click', function () { bumpZoom('live', -0.1); });
    if (zoomIn) zoomIn.addEventListener('click', function () { bumpZoom('live', 0.1); });
    var zoomOutR = root.querySelector('[data-cvs-zoom-out-review]');
    var zoomInR = root.querySelector('[data-cvs-zoom-in-review]');
    if (zoomOutR) zoomOutR.addEventListener('click', function () { bumpZoom('review', -0.1); });
    if (zoomInR) zoomInR.addEventListener('click', function () { bumpZoom('review', 0.1); });

    var resumeContinue = root.querySelector('[data-cvs-resume-continue]');
    if (resumeContinue) resumeContinue.addEventListener('click', function () {
        if (boot.resume_cv) loadCv(boot.resume_cv.id, boot.resume_cv.studio_step || 1);
    });
    var resumeDismiss = root.querySelector('[data-cvs-resume-dismiss]');
    if (resumeDismiss) resumeDismiss.addEventListener('click', function () {
        if (resumeBanner) resumeBanner.hidden = true;
        restart();
    });

    bindLeaveGuard();
    fillForm(profileForm, boot.seed || {});
    renderTemplates();
    renderColors();
    renderLibrary();
    applyZoom('live');
    applyZoom('review');

    if (boot.initial_cv_id) {
        loadCv(boot.initial_cv_id);
    } else {
        setStep(1, { skipSave: true });
        showResumeBanner();
    }
})();
