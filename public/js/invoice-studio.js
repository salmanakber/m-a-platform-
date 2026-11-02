/**
 * Invoice studio — async expert typeahead (safe for 10k+ experts)
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
        expert: null,
        productId: null,
        invoice: null,
        busy: false,
        searchTimer: null,
        searchSeq: 0,
    };

    var form = root.querySelector('[data-ins-form]');
    var toastEl = root.querySelector('[data-ins-toast]');
    var feedEl = root.querySelector('[data-ins-feed]');
    var barEl = root.querySelector('[data-ins-bar]');
    var pctEl = root.querySelector('[data-ins-pct]');
    var qInput = root.querySelector('[data-ins-expert-q]');
    var suggestBox = root.querySelector('[data-ins-experts]');
    var searchStatus = root.querySelector('[data-ins-search-status]');
    var searchHint = root.querySelector('[data-ins-search-hint]');
    var selectedWrap = root.querySelector('[data-ins-selected]');
    var searchWrap = root.querySelector('[data-ins-search-wrap]');
    var recentWrap = root.querySelector('[data-ins-recent-wrap]');

    function url(key, id) {
        return String(urls[key] || '').replace('__ID__', id != null ? id : (state.invoice && state.invoice.id) || '');
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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
            if (id !== 'library' && !isNaN(parseInt(id, 10)) && !isNaN(parseInt(n, 10))) {
                li.classList.toggle('is-done', parseInt(id, 10) < parseInt(n, 10));
            }
        });
        if (n === 'library') renderLibrary();
        if (n === 2) updateChosenExpertBanner();
        if (n === 4 && state.invoice) {
            setProgress(state.invoice.confirmation_sent_at ? 50 : 0, 'Bereit zum Versand', state.invoice.invoice_number || '');
            if (state.invoice.confirmation_sent_at) markMail('confirm', 'done');
            if (state.invoice.invoice_sent_at) markMail('invoice', 'done');
        }
    }

    function expertMeta(e) {
        if (!e) return '';
        var bits = [];
        if (e.group_label) bits.push(e.group_label);
        if (e.contact_name) bits.push(e.contact_name);
        if (e.email) bits.push(e.email);
        return bits.join(' · ');
    }

    function selectExpert(expert, advance) {
        if (!expert || !expert.id) return;
        state.expertId = expert.id;
        state.expert = expert;
        renderSelected();
        hideSuggest();
        if (qInput) qInput.value = '';
        var createBtn = root.querySelector('[data-ins-create]');
        if (createBtn) createBtn.disabled = !(state.expertId && state.productId);
        if (advance) setStep(2);
    }

    function clearExpert() {
        state.expertId = null;
        state.expert = null;
        renderSelected();
        if (qInput) {
            qInput.value = '';
            qInput.focus();
        }
        hideSuggest();
        if (searchHint) searchHint.textContent = 'Mindestens 2 Zeichen — max. 12 Treffer live.';
        var createBtn = root.querySelector('[data-ins-create]');
        if (createBtn) createBtn.disabled = true;
    }

    function renderSelected() {
        if (!selectedWrap) return;
        var has = !!(state.expert && state.expertId);
        selectedWrap.hidden = !has;
        if (searchWrap) searchWrap.hidden = has;
        if (recentWrap) recentWrap.hidden = has;
        if (!has) return;
        var nameEl = root.querySelector('[data-ins-selected-name]');
        var metaEl = root.querySelector('[data-ins-selected-meta]');
        if (nameEl) nameEl.textContent = state.expert.company_name || ('#' + state.expert.id);
        if (metaEl) metaEl.textContent = expertMeta(state.expert);
    }

    function updateChosenExpertBanner() {
        var banner = root.querySelector('[data-ins-chosen-expert]');
        var name = root.querySelector('[data-ins-chosen-name]');
        if (!banner) return;
        if (state.expert) {
            banner.hidden = false;
            if (name) name.textContent = state.expert.company_name || ('#' + state.expertId);
        } else {
            banner.hidden = true;
        }
    }

    function hideSuggest() {
        if (suggestBox) {
            suggestBox.hidden = true;
            suggestBox.innerHTML = '';
        }
        if (searchStatus) searchStatus.hidden = true;
    }

    function renderSuggest(list, q) {
        if (!suggestBox) return;
        if (!list.length) {
            suggestBox.hidden = false;
            suggestBox.innerHTML = '<div class="ins__suggest-empty">Keine Treffer für «' + escapeHtml(q) + '»</div>';
            return;
        }
        suggestBox.hidden = false;
        suggestBox.innerHTML = list.map(function (e) {
            return '<button type="button" class="ins__suggest-item" role="option" data-expert-id="' + e.id + '">' +
                '<strong>' + escapeHtml(e.company_name || '') + '</strong>' +
                '<span>' + escapeHtml(expertMeta(e)) + '</span>' +
                '</button>';
        }).join('');
        suggestBox.querySelectorAll('[data-expert-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-expert-id'), 10);
                var expert = list.find(function (x) { return x.id === id; });
                selectExpert(expert, true);
            });
        });
    }

    function scheduleSearch(raw) {
        clearTimeout(state.searchTimer);
        var q = String(raw || '').trim();
        if (q.length < 2) {
            hideSuggest();
            if (searchHint) searchHint.textContent = 'Mindestens 2 Zeichen — max. 12 Treffer live.';
            return;
        }
        if (searchStatus) {
            searchStatus.hidden = false;
            searchStatus.textContent = 'Suche…';
        }
        state.searchTimer = setTimeout(function () { runSearch(q); }, 280);
    }

    async function runSearch(q) {
        if (!urls.search_experts) return;
        var seq = ++state.searchSeq;
        try {
            var endpoint = urls.search_experts + (urls.search_experts.indexOf('?') >= 0 ? '&' : '?') +
                'q=' + encodeURIComponent(q) + '&limit=12';
            var res = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            var data = await res.json();
            if (seq !== state.searchSeq) return;
            if (!res.ok) throw new Error((data && data.message) || 'Suche fehlgeschlagen');
            if (searchStatus) {
                searchStatus.hidden = false;
                searchStatus.textContent = (data.count || 0) + ' Treffer';
            }
            if (searchHint) searchHint.textContent = 'Klicken zum Auswählen — Seite lädt nie die gesamte Expertenliste.';
            renderSuggest(data.experts || [], q);
        } catch (err) {
            if (seq !== state.searchSeq) return;
            hideSuggest();
            toast(err.message || 'Suche fehlgeschlagen', 'error');
        }
    }

    function renderRecent() {
        var box = root.querySelector('[data-ins-recent]');
        if (!box) return;
        var list = boot.recent_experts || [];
        if (!list.length) {
            if (recentWrap) recentWrap.hidden = true;
            return;
        }
        if (recentWrap) recentWrap.hidden = !!state.expertId;
        box.innerHTML = list.map(function (e) {
            return '<button type="button" class="ins__recent-item" data-recent-id="' + e.id + '">' +
                '<strong>' + escapeHtml(e.company_name || '') + '</strong>' +
                '<span>' + escapeHtml(e.email || '') + '</span></button>';
        }).join('');
        box.querySelectorAll('[data-recent-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-recent-id'), 10);
                var expert = list.find(function (x) { return x.id === id; });
                selectExpert(expert, true);
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
        try {
            var res = await api('POST', urls.store, {
                expert_id: state.expertId,
                invoice_product_id: state.productId,
                billing_email: state.expert ? state.expert.email : null,
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
            state.expert = {
                id: state.invoice.expert_id,
                company_name: state.invoice.expert_name,
                email: state.invoice.expert_email || state.invoice.billing_email,
            };
            state.productId = state.invoice.invoice_product_id;
            renderSelected();
            renderProducts();
            fillForm(state.invoice);
            setStep(3);
            toast('Rechnung geladen', 'ok');
        } catch (err) { toast(err.message, 'error'); }
    }

    function restart() {
        state.expertId = null;
        state.expert = null;
        state.productId = null;
        state.invoice = null;
        if (feedEl) feedEl.innerHTML = '';
        markMail('confirm', '');
        markMail('invoice', '');
        setProgress(0, 'Bereit zum Versand', 'Schritt für Schritt wählen');
        clearExpert();
        renderRecent();
        renderProducts();
        setStep(1);
        toast('Neue Rechnung gestartet');
    }

    root.querySelectorAll('[data-ins-goto]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var n = btn.getAttribute('data-ins-goto');
            if (n === '4') { saveDraft().then(function () { setStep(4); }).catch(function (e) { toast(e.message, 'error'); }); return; }
            if (n === '2' && !state.expertId) { toast('Bitte zuerst einen Experten wählen', 'error'); return; }
            setStep(n === 'library' ? 'library' : parseInt(n, 10));
        });
    });
    root.querySelectorAll('[data-ins-step]').forEach(function (li) {
        li.addEventListener('click', function () {
            var id = li.getAttribute('data-ins-step');
            if (id === 'library') { setStep('library'); return; }
            var n = parseInt(id, 10);
            if (n >= 2 && !state.expertId && !state.invoice) return;
            if (n >= 3 && !state.invoice) return;
            setStep(n);
        });
    });

    if (qInput) {
        qInput.addEventListener('input', function () { scheduleSearch(qInput.value); });
        qInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideSuggest();
            if (e.key === 'Enter') {
                e.preventDefault();
                var first = suggestBox && suggestBox.querySelector('[data-expert-id]');
                if (first) first.click();
            }
        });
        qInput.addEventListener('focus', function () {
            if (String(qInput.value || '').trim().length >= 2) scheduleSearch(qInput.value);
        });
    }
    document.addEventListener('click', function (e) {
        if (!suggestBox || suggestBox.hidden) return;
        if (root.querySelector('[data-ins-expert-picker]') && root.querySelector('[data-ins-expert-picker]').contains(e.target)) return;
        hideSuggest();
    });

    var clearBtn = root.querySelector('[data-ins-clear-expert]');
    if (clearBtn) clearBtn.addEventListener('click', clearExpert);
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

    renderSelected();
    renderRecent();
    renderProducts();
    renderLibrary();
    if (boot.initial_invoice_id) loadInvoice(boot.initial_invoice_id);
    else setStep(1);
})();
