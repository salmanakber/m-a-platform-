@extends('layouts.expert')

@section('title', 'Website-Import')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Website-Import</span>
@endsection

@section('content')
@php
    $jobLabel = [
        'pending' => 'Wartend',
        'running' => 'Läuft',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
    ];
    $jobPill = [
        'pending' => 'warning',
        'running' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
    ];
    $canRun = $expert
        && $expert->status === 'approved'
        && ! $hasPending
        && ($cooldownUntil === null || $cooldownUntil->isPast());
@endphp

<div class="xp-page" id="crawlPage"
     data-run-url="{{ route('expert.crawl.run') }}"
     data-cooldown-until="{{ $cooldownUntil && $cooldownUntil->isFuture() ? $cooldownUntil->toIso8601String() : '' }}"
     data-can-run="{{ $canRun ? '1' : '0' }}"
     data-approved="{{ ($expert && $expert->status === 'approved') ? '1' : '0' }}">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Inhalte</p>
            <h1>Website-Import</h1>
            <p>Scannt Ihre Website nach Blog-Beiträgen und Events. Gefundene Inhalte landen als Entwurf — Sie bestätigen vor der Veröffentlichung.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--small" href="{{ route('expert.events.create') }}">Event manuell erstellen</a>
            <a class="btn btn--ghost btn--small" href="{{ route('expert.articles.create') }}">Artikel schreiben</a>
        </div>
    </header>

    @if (!$expert)
        <div class="xp-empty">Kein Expertenprofil vorhanden.</div>
    @else
        @if ($expert->status !== 'approved')
            <div class="xp-notice xp-notice--warn">
                <strong>Profil noch nicht freigegeben</strong>
                <p>Sobald Ihr Profil freigegeben ist, können Sie den Website-Import starten.</p>
            </div>
        @endif

        <div class="xp-action-strip">
            <div>
                <strong>Manuell bleibt immer möglich</strong>
                <p>Events und Artikel können Sie jederzeit selbst anlegen — unabhängig vom Website-Scan.</p>
            </div>
            <div class="xp-action-strip__btns">
                <a class="btn btn--small" href="{{ route('expert.events.create') }}">Neues Event</a>
                <a class="btn btn--ghost btn--small" href="{{ route('expert.articles.index') }}">Entwürfe prüfen</a>
            </div>
        </div>

        <div class="xp-crawl-layout">
            <section class="xp-panel">
                <div class="xp-panel__head">
                    <div>
                        <h2>Import steuern</h2>
                        <p>Adresse, Inhaltstypen und optionale KI-Nachbearbeitung.</p>
                    </div>
                    <span class="pill pill--{{ $expert->crawl_enabled ? 'success' : 'muted' }}">
                        {{ $expert->crawl_enabled ? 'Aktiv' : 'Inaktiv' }}
                    </span>
                </div>

                <form method="post" action="{{ route('expert.crawl.update') }}" class="xp-crawl-form" id="crawlSettingsForm">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="website_crawl_url">Website-Adresse *</label>
                        <input class="form-control" type="url" name="website_crawl_url" id="website_crawl_url"
                               value="{{ old('website_crawl_url', $expert->website_crawl_url ?: $expert->website) }}"
                               placeholder="https://www.ihre-firma.ch/blog" required>
                        <p class="field-hint">Am besten Blog-/News- oder Event-Seite — nicht nur die Startseite.</p>
                    </div>

                    <fieldset class="xp-crawl-opts">
                        <legend>Was soll gescannt werden?</legend>
                        <label class="xp-toggle">
                            <input type="checkbox" name="crawl_import_articles" value="1" @checked(old('crawl_import_articles', $expert->crawl_import_articles ?? true))>
                            <span class="xp-toggle__ui" aria-hidden="true"></span>
                            <span class="xp-toggle__copy">
                                <strong>Blog-Artikel finden</strong>
                                <em>Als Entwurf speichern — Veröffentlichung erst nach Ihrer Bestätigung.</em>
                            </span>
                        </label>
                        <label class="xp-toggle">
                            <input type="checkbox" name="crawl_import_events" value="1" @checked(old('crawl_import_events', $expert->crawl_import_events ?? true))>
                            <span class="xp-toggle__ui" aria-hidden="true"></span>
                            <span class="xp-toggle__copy">
                                <strong>Events / Workshops finden</strong>
                                <em>Als Entwurf speichern. Fehlende Daten werden nicht erfunden.</em>
                            </span>
                        </label>
                        <label class="xp-toggle">
                            <input type="checkbox" name="crawl_use_ai" value="1" @checked(old('crawl_use_ai', $expert->crawl_use_ai ?? true))>
                            <span class="xp-toggle__ui" aria-hidden="true"></span>
                            <span class="xp-toggle__copy">
                                <strong>KI-Textbereinigung erlauben</strong>
                                <em>Optional Titel/Text glätten — nie automatisch veröffentlichen.</em>
                            </span>
                        </label>
                    </fieldset>

                    <label class="xp-toggle">
                        <input type="checkbox" name="crawl_enabled" value="1" @checked(old('crawl_enabled', $expert->crawl_enabled))>
                        <span class="xp-toggle__ui" aria-hidden="true"></span>
                        <span class="xp-toggle__copy">
                            <strong>Automatischen Import erlauben</strong>
                            <em>Freigegebene Profile können auch im täglichen Lauf berücksichtigt werden.</em>
                        </span>
                    </label>

                    <div class="xp-crawl-form__actions">
                        <button type="submit" class="btn btn--ghost">Einstellungen speichern</button>
                    </div>
                </form>

                <div class="xp-crawl-run">
                    <div>
                        <strong>Jetzt scannen</strong>
                        <p id="crawlRunHint">
                            @if ($hasPending)
                                Ein Import läuft gerade…
                            @elseif ($cooldownUntil && $cooldownUntil->isFuture())
                                Erneuter Import in <span id="crawlCooldownText">—</span>
                            @elseif ($expert->status !== 'approved')
                                Verfügbar nach Freigabe Ihres Profils.
                            @else
                                Scannt Ihre Website — Treffer landen zur Bestätigung als Entwurf.
                            @endif
                        </p>
                    </div>
                    <button type="button" class="btn" id="crawlStartBtn" @disabled(! $canRun)>
                        <span id="crawlStartLabel">{{ $hasPending ? 'Läuft…' : 'Scan starten' }}</span>
                    </button>
                </div>

                <div class="xp-cooldown-bar" id="crawlCooldownBar" @if(!($cooldownUntil && $cooldownUntil->isFuture())) hidden @endif>
                    <div class="xp-cooldown-bar__track">
                        <span id="crawlCooldownFill"></span>
                    </div>
                    <p>Nächster Import möglich in <strong id="crawlCooldownStrong">—</strong></p>
                </div>
            </section>

            <aside class="xp-side">
                <section class="xp-panel">
                    <div class="xp-panel__head"><div><h2>Übersicht</h2></div></div>
                    <div class="xp-mini-stats">
                        <div><span>Erfolgreich</span><strong id="statCompleted">{{ $completedCount }}</strong></div>
                        <div><span>Fehlgeschlagen</span><strong id="statFailed">{{ $failedCount }}</strong></div>
                    </div>
                    @if ($lastJob)
                        <p class="xp-muted" id="lastJobMeta">
                            Letzter Lauf: {{ $lastJob->created_at->format('d.m.Y H:i') }}
                            · <span class="pill pill--{{ $jobPill[$lastJob->status] ?? 'muted' }}">{{ $jobLabel[$lastJob->status] ?? $lastJob->status }}</span>
                        </p>
                        @if ($lastJob->notes)
                            <p class="xp-muted" id="lastJobNotes">{{ $lastJob->notes }}</p>
                        @endif
                    @else
                        <p class="xp-muted" id="lastJobMeta">Noch keine Importe vorhanden.</p>
                    @endif
                </section>

                <section class="xp-panel">
                    <div class="xp-panel__head"><div><h2>Ablauf</h2></div></div>
                    <ol class="xp-steps">
                        <li><strong>Inhaltstypen wählen</strong> — Blog und/oder Events</li>
                        <li><strong>Scan starten</strong> — Fortschritt live im Popup</li>
                        <li><strong>Entwürfe prüfen</strong> — nichts geht ohne Ihre Freigabe live</li>
                        <li><strong>Oder manuell</strong> — Event/Artikel selbst anlegen</li>
                    </ol>
                </section>
            </aside>
        </div>

        <section class="xp-panel" style="margin-top:1.1rem;">
            <div class="xp-panel__head">
                <div>
                    <h2>Verlauf</h2>
                    <p>Die letzten Website-Scans für Ihr Profil.</p>
                </div>
            </div>
            @if ($jobs->isEmpty())
                <div class="xp-empty" id="crawlHistoryEmpty">
                    <strong>Noch kein Verlauf</strong>
                    <p>Speichern Sie die Adresse und starten Sie Ihren ersten Scan.</p>
                </div>
            @else
                <div class="table-scroll" id="crawlHistoryTable">
                    <table class="entity-table">
                        <thead>
                            <tr>
                                <th>Gestartet</th>
                                <th>Status</th>
                                <th>Gefunden</th>
                                <th>Ergebnisse</th>
                                <th>Hinweise</th>
                                <th>Dauer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                <tr>
                                    <td>{{ $job->created_at->format('d.m.Y H:i') }}</td>
                                    <td><span class="pill pill--{{ $jobPill[$job->status] ?? 'muted' }}">{{ $jobLabel[$job->status] ?? $job->status }}</span></td>
                                    <td>{{ $job->urls_processed }}/{{ $job->urls_discovered }}</td>
                                    <td>{{ $job->results_count }}</td>
                                    <td>{{ $job->errors_count > 0 ? $job->errors_count.' Hinweis(e)' : '—' }}</td>
                                    <td>
                                        @if ($job->started_at && $job->finished_at)
                                            {{ $job->started_at->diffInSeconds($job->finished_at) }}s
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                @if ($job->notes)
                                    <tr class="xp-job-notes">
                                        <td colspan="6">{{ $job->notes }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>

<script>
(function () {
    var page = document.getElementById('crawlPage');
    if (!page) return;

    var btn = document.getElementById('crawlStartBtn');
    var label = document.getElementById('crawlStartLabel');
    var hint = document.getElementById('crawlRunHint');
    var urlInput = document.getElementById('website_crawl_url');
    var cooldownBar = document.getElementById('crawlCooldownBar');
    var cooldownFill = document.getElementById('crawlCooldownFill');
    var cooldownStrong = document.getElementById('crawlCooldownStrong');
    var pollTimer = null;
    var cooldownTimer = null;
    var cooldownUntil = page.getAttribute('data-cooldown-until') || '';
    var totalCooldown = {{ (int) ($cooldownSeconds ?? 120) }};
    var lastStep = '';
    var lastLabel = '';
    var finishedShown = false;

    var CRAWL_STEPS = [
        { id: 'queued', label: 'Warteschlange' },
        { id: 'discover', label: 'Website lesen' },
        { id: 'sitemap', label: 'Sitemap & Feeds' },
        { id: 'fetch', label: 'Seiten laden' },
        { id: 'finalize', label: 'Ergebnisse speichern' }
    ];

    var STEP_MAP = {
        queued: 'queued',
        discover: 'discover',
        sitemap: 'sitemap',
        found: 'sitemap',
        fetch: 'fetch',
        import: 'fetch',
        skip: 'fetch',
        finalize: 'finalize'
    };

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function formatRemain(sec) {
        sec = Math.max(0, sec);
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        return m > 0 ? (m + ' Min. ' + s + ' Sek.') : (s + ' Sek.');
    }

    function setCanRun(can) {
        if (!btn) return;
        btn.disabled = !can;
        if (can && label) label.textContent = 'Scan starten';
    }

    function startCooldown(iso) {
        cooldownUntil = iso || '';
        if (!cooldownUntil) {
            if (cooldownBar) cooldownBar.hidden = true;
            if (page.getAttribute('data-approved') === '1') {
                setCanRun(true);
                if (hint) hint.textContent = 'Scannt Ihre Website — Treffer landen zur Bestätigung als Entwurf.';
            }
            return;
        }
        if (cooldownTimer) clearInterval(cooldownTimer);
        function tick() {
            var end = Date.parse(cooldownUntil);
            var left = Math.ceil((end - Date.now()) / 1000);
            if (isNaN(end) || left <= 0) {
                if (cooldownTimer) clearInterval(cooldownTimer);
                cooldownTimer = null;
                if (cooldownBar) cooldownBar.hidden = true;
                setCanRun(page.getAttribute('data-approved') === '1');
                if (label) label.textContent = 'Scan starten';
                if (hint) hint.textContent = 'Bereit für erneuten Scan.';
                return;
            }
            setCanRun(false);
            if (label) label.textContent = 'Bitte warten…';
            if (cooldownBar) cooldownBar.hidden = false;
            var pct = Math.max(0, Math.min(100, ((totalCooldown - left) / totalCooldown) * 100));
            if (cooldownFill) cooldownFill.style.width = pct + '%';
            var text = formatRemain(left);
            if (cooldownStrong) cooldownStrong.textContent = text;
            if (hint) hint.innerHTML = 'Erneuter Scan in <strong>' + text + '</strong>';
        }
        tick();
        cooldownTimer = setInterval(tick, 250);
    }

    function openLive() {
        if (!window.LiveProgress) return;
        finishedShown = false;
        lastStep = '';
        lastLabel = '';
        LiveProgress.open({
            title: 'Website-Scan',
            subtitle: 'Live-Fortschritt: Seiten finden, laden, Entwürfe speichern',
            steps: CRAWL_STEPS,
            startLog: 'Scan wird gestartet…'
        });
        LiveProgress.setStats({ found: 0, loaded: 0, drafts: 0 });
        LiveProgress.activate('queued', 'Job in Warteschlange');
        LiveProgress.setPercent(3);
    }

    function applyStatus(data) {
        if (!window.LiveProgress) return;
        LiveProgress.setPercent(data.progress_percent || 0);
        LiveProgress.setStats({
            found: data.urls_discovered || 0,
            loaded: data.urls_processed || 0,
            drafts: data.results_count || 0
        });

        var raw = data.progress_step || 'queued';
        var mapped = STEP_MAP[raw] || 'fetch';
        if (mapped !== lastStep) {
            if (lastStep) LiveProgress.done(lastStep);
            LiveProgress.activate(mapped, data.progress_label || '');
            lastStep = mapped;
        }

        var lbl = data.progress_label || '';
        if (lbl && lbl !== lastLabel) {
            var kind = 'working';
            if (raw === 'import') kind = 'ok';
            if (raw === 'skip') kind = 'info';
            LiveProgress.log(lbl, kind);
            lastLabel = lbl;
        }

        if (data.finished && !finishedShown) {
            finishedShown = true;
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = null;
            CRAWL_STEPS.forEach(function (s) { LiveProgress.done(s.id); });
            var ok = data.status === 'completed';
            if (data.notes) LiveProgress.log(data.notes, ok ? 'ok' : 'error');
            LiveProgress.finish({
                ok: ok,
                title: ok ? 'Fertig — bitte Entwürfe prüfen' : 'Fehler',
                message: data.notes || (ok ? 'Scan abgeschlossen.' : 'Scan fehlgeschlagen.'),
                actionsHtml:
                    '<a class="btn btn--small" href="{{ route('expert.articles.index') }}">Artikel prüfen</a>' +
                    '<a class="btn btn--small" href="{{ route('expert.events.index') }}">Events prüfen</a>' +
                    '<button type="button" class="btn btn--ghost btn--small" data-lp-reload>Seite aktualisieren</button>' +
                    '<button type="button" class="btn btn--ghost btn--small" data-lp-close>Schliessen</button>'
            });
            if (data.cooldown_until) startCooldown(data.cooldown_until);
            else startCooldown(new Date(Date.now() + totalCooldown * 1000).toISOString());
        }
    }

    function poll(statusUrl) {
        if (pollTimer) clearInterval(pollTimer);
        function tick() {
            fetch(statusUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(applyStatus).catch(function () {});
        }
        tick();
        pollTimer = setInterval(tick, 600);
    }

    function fireProcess(processUrl) {
        fetch(processUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    if (btn) {
        btn.addEventListener('click', function () {
            var url = (urlInput && urlInput.value || '').trim();
            if (!url) {
                alert('Bitte eine Website-Adresse eingeben.');
                return;
            }
            if (!window.LiveProgress) {
                alert('Fortschrittsanzeige nicht geladen — bitte Seite neu laden.');
                return;
            }
            openLive();
            setCanRun(false);
            if (label) label.textContent = 'Läuft…';

            fetch(page.getAttribute('data-run-url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ website_crawl_url: url })
            }).then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            }).then(function (result) {
                if (result.status === 429) {
                    LiveProgress.finish({
                        ok: false,
                        title: 'Bitte warten',
                        message: result.data.message || 'Cooldown aktiv.'
                    });
                    if (result.data.cooldown_until) startCooldown(result.data.cooldown_until);
                    return;
                }
                if (result.status === 409 && result.data.status_url) {
                    LiveProgress.log('Läuft bereits — Fortschritt wird geladen…', 'working');
                    if (result.data.process_url) fireProcess(result.data.process_url);
                    poll(result.data.status_url);
                    return;
                }
                if (!result.ok) {
                    throw new Error((result.data && result.data.message) || 'Scan konnte nicht gestartet werden.');
                }
                LiveProgress.log('Job gestartet — Seiten werden gescannt…', 'ok');
                if (result.data.process_url) fireProcess(result.data.process_url);
                if (result.data.status_url) poll(result.data.status_url);
            }).catch(function (err) {
                LiveProgress.finish({
                    ok: false,
                    title: 'Fehler',
                    message: err.message || 'Fehler beim Start.'
                });
                setCanRun(page.getAttribute('data-approved') === '1');
                if (label) label.textContent = 'Scan starten';
            });
        });
    }

    if (cooldownUntil) startCooldown(cooldownUntil);
})();
</script>
@endsection
