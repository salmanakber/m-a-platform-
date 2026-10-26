/**
 * LiveProgress — chatbot-style realtime progress modal for crawl / AI jobs.
 * Usage:
 *   LiveProgress.open({ title, subtitle })
 *   LiveProgress.resetSteps([{ id, label }])
 *   LiveProgress.activate(id, detail?)
 *   LiveProgress.done(id, detail?)
 *   LiveProgress.log(text, kind?) // kind: info|ok|warn|error|working
 *   LiveProgress.setPercent(n)
 *   LiveProgress.setStats({ found, loaded, drafts })
 *   LiveProgress.finish({ ok, message, actionsHtml? })
 *   LiveProgress.close()
 */
(function (global) {
    'use strict';

    var root = null;
    var feed = null;
    var stepsEl = null;
    var titleEl = null;
    var subEl = null;
    var pctEl = null;
    var barEl = null;
    var statsEl = null;
    var footEl = null;
    var knownSteps = {};
    var lastLogKey = '';

    function ensureDom() {
        if (root) return;
        root = document.createElement('div');
        root.className = 'lp-modal';
        root.hidden = true;
        root.innerHTML =
            '<div class="lp-modal__backdrop" data-lp-backdrop></div>' +
            '<div class="lp-modal__card" role="dialog" aria-modal="true" aria-labelledby="lpTitle">' +
            '  <header class="lp-modal__head">' +
            '    <div class="lp-modal__pulse" aria-hidden="true"><span></span><span></span><span></span></div>' +
            '    <div>' +
            '      <h2 id="lpTitle" data-lp-title>Arbeitet…</h2>' +
            '      <p data-lp-sub>Bitte einen Moment warten.</p>' +
            '    </div>' +
            '    <div class="lp-modal__pct"><strong data-lp-pct>0%</strong></div>' +
            '  </header>' +
            '  <div class="lp-modal__bar" aria-hidden="true"><i data-lp-bar></i></div>' +
            '  <div class="lp-modal__stats" data-lp-stats hidden>' +
            '    <span><em data-lp-found>0</em> gefunden</span>' +
            '    <span><em data-lp-loaded>0</em> geladen</span>' +
            '    <span><em data-lp-drafts>0</em> Entwürfe</span>' +
            '  </div>' +
            '  <ol class="lp-modal__steps" data-lp-steps></ol>' +
            '  <div class="lp-modal__feed" data-lp-feed aria-live="polite"></div>' +
            '  <footer class="lp-modal__foot" data-lp-foot hidden></footer>' +
            '</div>';
        document.body.appendChild(root);
        feed = root.querySelector('[data-lp-feed]');
        stepsEl = root.querySelector('[data-lp-steps]');
        titleEl = root.querySelector('[data-lp-title]');
        subEl = root.querySelector('[data-lp-sub]');
        pctEl = root.querySelector('[data-lp-pct]');
        barEl = root.querySelector('[data-lp-bar]');
        statsEl = root.querySelector('[data-lp-stats]');
        footEl = root.querySelector('[data-lp-foot]');
        root.querySelector('[data-lp-backdrop]').addEventListener('click', function () {
            if (footEl && !footEl.hidden) close();
        });
    }

    function open(opts) {
        ensureDom();
        opts = opts || {};
        knownSteps = {};
        lastLogKey = '';
        feed.innerHTML = '';
        stepsEl.innerHTML = '';
        footEl.hidden = true;
        footEl.innerHTML = '';
        statsEl.hidden = true;
        titleEl.textContent = opts.title || 'Arbeitet…';
        subEl.textContent = opts.subtitle || 'Fortschritt in Echtzeit';
        setPercent(0);
        root.hidden = false;
        document.body.classList.add('lp-busy');
        if (opts.steps && opts.steps.length) resetSteps(opts.steps);
        log(opts.startLog || 'Gestartet…', 'working');
    }

    function resetSteps(list) {
        ensureDom();
        stepsEl.innerHTML = '';
        knownSteps = {};
        (list || []).forEach(function (s, i) {
            var li = document.createElement('li');
            li.setAttribute('data-lp-step', s.id);
            li.innerHTML = '<i></i><div><strong></strong><span></span></div>';
            li.querySelector('strong').textContent = s.label;
            if (i === 0) li.classList.add('is-active');
            stepsEl.appendChild(li);
            knownSteps[s.id] = li;
        });
    }

    function activate(id, detail) {
        ensureDom();
        Object.keys(knownSteps).forEach(function (key) {
            var el = knownSteps[key];
            if (el.classList.contains('is-active') && key !== id) {
                el.classList.remove('is-active');
                if (!el.classList.contains('is-done')) el.classList.add('is-done');
            }
        });
        var li = knownSteps[id];
        if (!li) return;
        li.classList.remove('is-done');
        li.classList.add('is-active');
        if (detail) li.querySelector('span').textContent = detail;
    }

    function done(id, detail) {
        ensureDom();
        var li = knownSteps[id];
        if (!li) return;
        li.classList.remove('is-active');
        li.classList.add('is-done');
        if (detail) li.querySelector('span').textContent = detail;
    }

    function log(text, kind) {
        ensureDom();
        if (!text) return;
        var key = (kind || 'info') + '|' + text;
        if (key === lastLogKey) return;
        lastLogKey = key;

        // demote previous working lines
        feed.querySelectorAll('.lp-line.is-working').forEach(function (el) {
            el.classList.remove('is-working');
            el.classList.add('is-ok');
            var mark = el.querySelector('.lp-line__mark');
            if (mark) mark.textContent = '✓';
        });

        var row = document.createElement('div');
        row.className = 'lp-line is-' + (kind || 'info');
        var mark = '•';
        if (kind === 'ok' || kind === 'done') mark = '✓';
        if (kind === 'working') mark = '◌';
        if (kind === 'warn') mark = '!';
        if (kind === 'error') mark = '✕';
        var time = new Date();
        var hh = String(time.getHours()).padStart(2, '0');
        var mm = String(time.getMinutes()).padStart(2, '0');
        var ss = String(time.getSeconds()).padStart(2, '0');
        row.innerHTML =
            '<span class="lp-line__mark">' + mark + '</span>' +
            '<span class="lp-line__text"></span>' +
            '<time>' + hh + ':' + mm + ':' + ss + '</time>';
        row.querySelector('.lp-line__text').textContent = text;
        feed.appendChild(row);
        feed.scrollTop = feed.scrollHeight;
    }

    function setPercent(n) {
        ensureDom();
        n = Math.max(0, Math.min(100, Math.round(n || 0)));
        pctEl.textContent = n + '%';
        barEl.style.width = n + '%';
    }

    function setStats(stats) {
        ensureDom();
        stats = stats || {};
        statsEl.hidden = false;
        root.querySelector('[data-lp-found]').textContent = stats.found != null ? stats.found : 0;
        root.querySelector('[data-lp-loaded]').textContent = stats.loaded != null ? stats.loaded : 0;
        root.querySelector('[data-lp-drafts]').textContent = stats.drafts != null ? stats.drafts : 0;
    }

    function finish(opts) {
        ensureDom();
        opts = opts || {};
        var ok = opts.ok !== false;
        setPercent(100);
        Object.keys(knownSteps).forEach(function (id) {
            knownSteps[id].classList.remove('is-active');
            knownSteps[id].classList.add('is-done');
        });
        titleEl.textContent = ok ? (opts.title || 'Fertig') : (opts.title || 'Fehler');
        subEl.textContent = opts.message || (ok ? 'Abgeschlossen.' : 'Vorgang fehlgeschlagen.');
        log(opts.message || (ok ? 'Abgeschlossen.' : 'Fehlgeschlagen.'), ok ? 'ok' : 'error');
        footEl.hidden = false;
        footEl.innerHTML = opts.actionsHtml ||
            '<button type="button" class="btn btn--small" data-lp-close>Schliessen</button>';
        var closeBtn = footEl.querySelector('[data-lp-close]');
        if (closeBtn) closeBtn.addEventListener('click', function () {
            close();
            if (typeof opts.onClose === 'function') opts.onClose();
        });
        footEl.querySelectorAll('[data-lp-reload]').forEach(function (btn) {
            btn.addEventListener('click', function () { window.location.reload(); });
        });
    }

    function close() {
        if (!root) return;
        root.hidden = true;
        document.body.classList.remove('lp-busy');
    }

    /**
     * Simulated staged AI run while waiting on a fetch promise.
     */
    function runAiStages(stages, promise) {
        stages = stages || [];
        open({
            title: 'KI arbeitet…',
            subtitle: 'Schritte in Echtzeit',
            steps: stages.map(function (s) { return { id: s.id, label: s.label }; }),
            startLog: stages[0] ? stages[0].log : 'KI gestartet…'
        });
        var i = 0;
        if (stages[0]) activate(stages[0].id, stages[0].detail || '');
        setPercent(8);
        var timer = setInterval(function () {
            if (i >= stages.length - 1) return;
            done(stages[i].id, 'erledigt');
            log(stages[i].doneLog || (stages[i].label + ' — fertig'), 'ok');
            i += 1;
            activate(stages[i].id, stages[i].detail || '');
            log(stages[i].log || stages[i].label, 'working');
            setPercent(12 + Math.round((i / Math.max(stages.length - 1, 1)) * 70));
        }, stages[0] && stages[0].ms ? stages[0].ms : 1600);

        return Promise.resolve(promise).then(function (result) {
            clearInterval(timer);
            stages.forEach(function (s) { done(s.id); });
            setPercent(96);
            return result;
        }, function (err) {
            clearInterval(timer);
            finish({ ok: false, message: (err && err.message) || 'Fehler' });
            throw err;
        });
    }

    global.LiveProgress = {
        open: open,
        close: close,
        resetSteps: resetSteps,
        activate: activate,
        done: done,
        log: log,
        setPercent: setPercent,
        setStats: setStats,
        finish: finish,
        runAiStages: runAiStages
    };
})(window);
