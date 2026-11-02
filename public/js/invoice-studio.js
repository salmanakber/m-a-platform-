/**
 * Invoice Control Room — AJAX wizard
 */
(function () {
    'use strict';
    var root = document.getElementById('invoiceStudio');
    if (!root) return;

    var boot;
    try { boot = JSON.parse(root.getAttribute('data-boot') || '{}'); }
    catch (e) { return; }

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var urls = boot.urls || {};
    var state = {
        step: 1,
        expertId: null,
        productId: null,
        invoice: null,
        busy: false,
    };

    var form = root.querySelector('[data-ins-form]');
    var toastEl = root.querySelector('[data-ins-toast]');
    var feedEl = root.querySelector('[data-ins-feed]');
    var barEl = root.querySelector('[data-ins-bar]');
    var pctEl = root.querySelector('[data-ins-pct]');

    function url(key, id) {
        return String(urls[key] || '').replace('__ID__', id != null ? id : (state.invoice && state.invoice.id) || '');
    }
    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function toast(msg, kind) {
        if (!toastEl) return;
        toastEl.hidden = false;
        toastEl.textContent = msg;
        toastEl.className = 'ins__toast' + (kind ? ' is-' + kind : '');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toastEl.hidden = true; }, 3200);
    }
    function log(text, kind) {
        if (!feedEl) return;
        var row = document.createElement('div');
        row.className = 'ins__feed-row' + (kind ? ' is-' + kind : '');
        row.innerHTML = '<time>' + new Date().toLocaleTimeString('de-CH', { hour:'2-digit', minute:'2-digit', second:'2-digit' }) + '</time><span>' + escapeHtml(text) + '</span>';
        feedEl.appendChild(row);
        feedEl.scrollTop = feedEl.scrollHeight;
    }
    function setProgress(pct, title, sub) {
        if (pctEl) pctEl.textContent = Math.round(pct) + '%';
        if (barEl) barEl.style.width = Math.max(0, Math.min(100, pct)) + '%';
        var t = root.querySelector('[data-ins-progress-title]');
        var s = root.querySelector('[data-ins-progress-sub]');
        if (t && title) t.textContent = title;
        if (s && sub) s.textContent = sub;
    }
    function markMail(which, status) {
        var li = root.querySelector('[data-ins-mail="' + which + '"]');
        if (!li) return;
        li.classList.remove('is-active', 'is-done', 'is-error');
        if (status) li.classList.add('is-' + status);
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
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) throw new Error((data && data.message) || ('Fehler ' + res.status));
        return data;
    }

    function setStep(n) {
        state.step = n;
        root.querySelectorAll('[data-ins-panel]').forEach(function (p) {
            var id = p.getAttribute('data-ins-panel');
            var on = String(id) === String(n);
            p.hidden = !on;
            p.classList.toggle('is-active', on);
        });
        root.querySelectorAll('[data-ins-step]').forEach(function (li) {
            var id = li.getAttribute('data-ins-step');
            li.classList.toggle('is-active', String(id) === String(n));
        });
        if (n === 'library') renderLibrary();
        if (n === 4 && state.invoice) {
            setProgress(state.invoice.confirmation_sent_at ? 50 : 0, 'Bereit zum Versand', state.invoice.invoice_number || '');
            if (state.invoice.confirmation_sent_at) markMail('confirm', 'done');
            if (state.invoice.invoice_sent_at) markMail('invoice', 'done');
        }
    }

    function renderExperts(q) {
        var box = root.querySelector('[data-ins-experts]');
        if (!box) return;
        q = (q || '').toLowerCase();
        var list = (boot.experts || []).filter(function (e) {
            if (!q) return true;
            return (e.company_name || '').toLowerCase().indexOf(q) !== -1 || (e.email || '').toLowerCase().indexOf(q) !== -1;
        });
        box.innerHTML = list.map(function (e) {
            var on = state.expertId === e.id ? ' is-on' : '';
            return '<button type="button" class="ins__pick' + on + '" data-expert="' + e.id + '"><strong>' + escapeHtml(e.company_name) + '</strong><span>' + escapeHtml(e.email || '') + '</span></button>';
        }).join('') || '<p class="ins__empty">Keine Treffer</p>';
        box.querySelectorAll('[data-expert]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.expertId = parseInt(btn.getAttribute('data-expert'), 10);
                renderExperts(root.querySelector('[data-ins-expert-q]').value);
                setStep(2);
            });
        });
    }

    function renderProducts() {
        var box = root.querySelector('[data-ins-products]');
        if (!box) return;
        box.innerHTML = (boot.products || []).map(function (p) {
            var on = state.productId === p.id ? ' is-on' : '';
            return '<button type="button" class="ins__product' + on + '" data-product="' + p.id + '">' +
                '<strong>' + escapeHtml(p.name) + '</strong>' +
                '<em>' + escapeHtml(p.unit_price_label) + ' ' + escapeHtml(p.currency) + '</em>' +
                '<span>' + escapeHtml(p.description || p.sku || '') + '</span></button>';
        }).join('') || '<p class="ins__empty">Keine Produkte — bitte zuerst Katalog pflegen.</p>';
        box.querySelectorAll('[data-product]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.productId = parseInt(btn.getAttribute('data-product'), 10);
                renderProducts();
                root.querySelector('[data-ins-create]').disabled = !(state.expertId && state.productId);
            });
        });
        root.querySelector('[data-ins-create]').disabled = !(state.expertId && state.productId);
    }

    function fillForm(inv) {
        if (!form || !inv) return;
        form.invoice_number.value = inv.invoice_number || '';
        form.invoice_date.value = inv.invoice_date || '';
        form.amount.value = inv.amount != null ? inv.amount : '';
        form.billing_email.value = inv.billing_email || '';
        form.service_title.value = inv.service_title || '';
        form.service_detail.value = inv.service_detail || '';
        form.notes.value = inv.notes || '';
        form.admin_notes.value = inv.admin_notes || '';
    }

    function readForm() {
        return {
            invoice_date: form.invoice_date.value,
            amount: form.amount.value,
            billing_email: form.billing_email.value,
            service_title: form.service_title.value,
            service_detail: form.service_detail.value,
            notes: form.notes.value,
            admin_notes: form.admin_notes.value,
            currency: 'CHF',
            invoice_product_id: state.invoice && state.invoice.invoice_product_id,
        };
    }

    function upsertInvoice(inv) {
        boot.invoices = boot.invoices || [];
        var found = false;
        boot.invoices = boot.invoices.map(function (i) {
            if (i.id === inv.id) { found = true; return inv; }
            return i;
        });
        if (!found) boot.invoices.unshift(inv);
        renderLibrary();
    }

    function renderLibrary() {
        var box = root.querySelector('[data-ins-library]');
        if (!box) return;
        var list = boot.invoices || [];
        if (!list.length) {
            box.innerHTML = '<div class="ins__empty">Noch keine Rechnungen.</div>';
            return;
        }
        box.innerHTML = list.map(function (inv) {
            return '<article class="ins__card">' +
                '<strong>' + escapeHtml(inv.invoice_number) + '</strong>' +
                '<span>' + escapeHtml(inv.expert_name) + ' · ' + escapeHtml(inv.amount_label) + ' ' + escapeHtml(inv.currency) + '</span>' +
                '<span>' + escapeHtml((inv.service_title || '') + ' · ' + (inv.updated_label || '')) + '</span>' +
                '<div class="ins__card-actions"><button type="button" class="btn btn--small" data-load-inv="' + inv.id + '">Öffnen</button></div>' +
                '</article>';
        }).join('');
        box.querySelectorAll('[data-load-inv]').forEach(function (btn) {
            btn.addEventListener('click', function () { loadInvoice(parseInt(btn.getAttribute('data-load-inv'), 10)); });
        });
    }

    async function createDraft() {
        if (!state.expertId || !state.productId) return;
        var expert = (boot.experts || []).find(function (e) { return e.id === state.expertId; });
        try {
            var res = await api('POST', urls.store, {
                expert_id: state.expertId,
                invoice_product_id: state.productId,
                billing_email: expert ? expert.email : null,
            });
            state.invoice = res.invoice;
            upsertInvoice(state.invoice);
            fillForm(state.invoice);
            setStep(3);
            toast('Entwurf erstellt', 'ok');
        } catch (err) { toast(err.message, 'error'); }
    }

    async function saveDraft() {
        if (!state.invoice) return;
        try {
            var res = await api('PUT', url('update', state.invoice.id), readForm());
            state.invoice = res.invoice;
            upsertInvoice(state.invoice);
            toast('Gespeichert', 'ok');
        } catch (err) { toast(err.message, 'error'); }
    }

    async function sendConfirm() {
        if (!state.invoice || state.busy) return;
        state.busy = true;
        markMail('confirm', 'active');
        setProgress(20, 'Bestätigung wird gesendet…', state.invoice.billing_email || '');
        log('Sende Bestätigungs-E-Mail…', 'working');
        try {
            await saveDraft();
            var res = await api('POST', url('confirm', state.invoice.id), {});
            state.invoice = res.invoice;
            upsertInvoice(state.invoice);
            markMail('confirm', 'done');
            setProgress(50, 'Bestätigung gesendet', res.message || '');
            log(res.message || 'Bestätigung ok', 'ok');
            toast(res.message || 'Bestätigung gesendet', 'ok');
        } catch (err) {
            markMail('confirm', 'error');
            log(err.message, 'error');
            toast(err.message, 'error');
        } finally { state.busy = false; }
    }

    async function sendInvoice() {
        if (!state.invoice || state.busy) return;
        state.busy = true;
        markMail('invoice', 'active');
        setProgress(70, 'Rechnung wird gesendet…', state.invoice.billing_email || '');
        log('Sende Rechnungs-E-Mail…', 'working');
        try {
            await saveDraft();
            var res = await api('POST', url('send', state.invoice.id), { type: 'created' });
            state.invoice = res.invoice;
            upsertInvoice(state.invoice);
            markMail('invoice', 'done');
            setProgress(100, 'Rechnung gesendet', res.message || '');
            log(res.message || 'Rechnung ok', 'ok');
            var msg = root.querySelector('[data-ins-done-msg]');
            if (msg) msg.textContent = res.message || 'Rechnung wurde versendet.';
            setStep(5);
            toast(res.message || 'Rechnung gesendet', 'ok');
        } catch (err) {
            markMail('invoice', 'error');
            log(err.message, 'error');
            toast(err.message, 'error');
        } finally { state.busy = false; }
    }

    async function loadInvoice(id) {
        try {
            var res = await api('GET', url('show', id));
            state.invoice = res.invoice;
            state.expertId = state.invoice.expert_id;
            state.productId = state.invoice.invoice_product_id;
            fillForm(state.invoice);
            setStep(3);
            toast('Rechnung geladen', 'ok');
        } catch (err) { toast(err.message, 'error'); }
    }

    function restart() {
        state.expertId = null;
        state.productId = null;
        state.invoice = null;
        if (feedEl) feedEl.innerHTML = '';
        markMail('confirm', '');
        markMail('invoice', '');
        setProgress(0, 'Bereit zum Versand', 'Schritt für Schritt wählen');
        renderExperts('');
        renderProducts();
        setStep(1);
        toast('Neue Rechnung gestartet');
    }

    root.querySelectorAll('[data-ins-goto]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var n = btn.getAttribute('data-ins-goto');
            if (n === '4') { saveDraft().then(function () { setStep(4); }).catch(function (e) { toast(e.message, 'error'); }); return; }
            setStep(n === 'library' ? 'library' : parseInt(n, 10));
        });
    });
    root.querySelectorAll('[data-ins-step]').forEach(function (li) {
        li.addEventListener('click', function () {
            var id = li.getAttribute('data-ins-step');
            if (id === 'library') { setStep('library'); return; }
            var n = parseInt(id, 10);
            if (n >= 3 && !state.invoice) return;
            setStep(n);
        });
    });
    var q = root.querySelector('[data-ins-expert-q]');
    if (q) q.addEventListener('input', function () { renderExperts(q.value); });
    var createBtn = root.querySelector('[data-ins-create]');
    if (createBtn) createBtn.addEventListener('click', createDraft);
    var saveBtn = root.querySelector('[data-ins-save]');
    if (saveBtn) saveBtn.addEventListener('click', function () { saveDraft(); });
    var cBtn = root.querySelector('[data-ins-send-confirm]');
    if (cBtn) cBtn.addEventListener('click', sendConfirm);
    var iBtn = root.querySelector('[data-ins-send-invoice]');
    if (iBtn) iBtn.addEventListener('click', sendInvoice);
    root.querySelectorAll('[data-ins-restart]').forEach(function (btn) {
        btn.addEventListener('click', restart);
    });

    renderExperts('');
    renderProducts();
    renderLibrary();
    if (boot.initial_invoice_id) loadInvoice(boot.initial_invoice_id);
    else setStep(1);
})();
