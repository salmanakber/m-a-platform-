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

<div class="xp-crawl-modal" id="crawlModal" hidden>
    <div class="xp-crawl-modal__backdrop" id="crawlModalBackdrop"></div>
    <div class="xp-crawl-modal__card xp-crawl-modal__card--compact" role="dialog" aria-modal="true" aria-labelledby="crawlModalTitle">
        <div class="xp-crawl-modal__row">
            <div class="xp-crawl-ring xp-crawl-ring--sm" aria-hidden="true">
                <svg viewBox="0 0 120 120">
                    <circle class="xp-crawl-ring__track" cx="60" cy="60" r="52"></circle>
                    <circle class="xp-crawl-ring__value" id="crawlRingValue" cx="60" cy="60" r="52"></circle>
                </svg>
                <div class="xp-crawl-ring__center">
                    <strong id="crawlRingPct">0%</strong>
                </div>
            </div>
            <div class="xp-crawl-modal__copy">
                <h2 id="crawlModalTitle">Website-Scan</h2>
                <p class="xp-crawl-modal__live" id="crawlLiveLabel">Startet…</p>
                <div class="xp-crawl-modal__stats xp-crawl-modal__stats--inline" id="crawlModalStats">
                    <span><em id="mDisc">0</em> gefunden</span>
                    <span><em id="mProc">0</em> geladen</span>
                    <span><em id="mRes">0</em> Entwürfe</span>
                </div>
            </div>
        </div>

        <div class="xp-crawl-mini-track" aria-hidden="true">
            <span id="crawlMiniFill"></span>
        </div>

        <p class="xp-crawl-modal__notes" id="crawlModalNotes" hidden></p>

        <div class="xp-crawl-modal__actions" id="crawlModalActions" hidden>
            <a class="btn btn--small" href="{{ route('expert.articles.index') }}">Artikel prüfen</a>
            <a class="btn btn--small" href="{{ route('expert.events.index') }}">Events prüfen</a>
            <button type="button" class="btn btn--ghost btn--small" id="crawlModalReload">Seite aktualisieren</button>
            <button type="button" class="btn btn--ghost btn--small" id="crawlModalClose">Schliessen</button>
        </div>
    </div>
</div>

<script>
(function () {
    var page = document.getElementById('crawlPage');
    if (!page) return;

    var btn = document.getElementById('crawlStartBtn');
    var label = document.getElementById('crawlStartLabel');
    var hint = document.getElementById('crawlRunHint');
    var urlInput = document.getElementById('website_crawl_url');
    var modal = document.getElementById('crawlModal');
    var ring = document.getElementById('crawlRingValue');
    var ringPct = document.getElementById('crawlRingPct');
    var liveLabel = document.getElementById('crawlLiveLabel');
    var miniFill = document.getElementById('crawlMiniFill');
    var notes = document.getElementById('crawlModalNotes');
    var actions = document.getElementById('crawlModalActions');
    var cooldownBar = document.getElementById('crawlCooldownBar');
    var cooldownFill = document.getElementById('crawlCooldownFill');
    var cooldownStrong = document.getElementById('crawlCooldownStrong');
    var CIRC = 2 * Math.PI * 52;
    var pollTimer = null;
    var cooldownTimer = null;
    var cooldownUntil = page.getAttribute('data-cooldown-until') || '';
    var totalCooldown = {{ (int) ($cooldownSeconds ?? 120) }};

    if (ring) {
        ring.style.strokeDasharray = String(CIRC);
        ring.style.strokeDashoffset = String(CIRC);
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setRing(pct) {
        pct = Math.max(0, Math.min(100, pct || 0));
        if (ring) ring.style.strokeDashoffset = String(CIRC - (CIRC * pct / 100));
        if (ringPct) ringPct.textContent = Math.round(pct) + '%';
        if (miniFill) miniFill.style.width = pct + '%';
    }

    function openModal() {
        modal.hidden = false;
        document.body.classList.add('xp-crawl-busy');
        actions.hidden = true;
        notes.hidden = true;
        setRing(3);
        liveLabel.textContent = 'Scan wird gestartet…';
        document.getElementById('crawlModalTitle').textContent = 'Website-Scan';
        document.getElementById('mDisc').textContent = '0';
        document.getElementById('mProc').textContent = '0';
        document.getElementById('mRes').textContent = '0';
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('xp-crawl-busy');
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
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

    function applyStatus(data) {
        setRing(data.progress_percent || 0);
        liveLabel.textContent = data.progress_label || 'Arbeitet…';
        document.getElementById('mDisc').textContent = data.urls_discovered || 0;
        document.getElementById('mProc').textContent = data.urls_processed || 0;
        document.getElementById('mRes').textContent = data.results_count || 0;

        if (data.finished) {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = null;
            actions.hidden = false;
            if (data.notes) {
                notes.hidden = false;
                notes.textContent = data.notes;
            }
            document.getElementById('crawlModalTitle').textContent =
                data.status === 'completed' ? 'Fertig — bitte Entwürfe prüfen' : 'Fehler';
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
            openModal();
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
                    liveLabel.textContent = result.data.message || 'Bitte warten…';
                    if (result.data.cooldown_until) startCooldown(result.data.cooldown_until);
                    actions.hidden = false;
                    return;
                }
                if (result.status === 409 && result.data.status_url) {
                    liveLabel.textContent = 'Läuft bereits — Fortschritt wird geladen…';
                    if (result.data.process_url) fireProcess(result.data.process_url);
                    poll(result.data.status_url);
                    return;
                }
                if (!result.ok) {
                    throw new Error((result.data && result.data.message) || 'Scan konnte nicht gestartet werden.');
                }
                if (result.data.process_url) fireProcess(result.data.process_url);
                if (result.data.status_url) poll(result.data.status_url);
            }).catch(function (err) {
                liveLabel.textContent = err.message || 'Fehler beim Start.';
                document.getElementById('crawlModalTitle').textContent = 'Fehler';
                actions.hidden = false;
                setCanRun(page.getAttribute('data-approved') === '1');
                if (label) label.textContent = 'Scan starten';
            });
        });
    }

    var closeBtn = document.getElementById('crawlModalClose');
    var reloadBtn = document.getElementById('crawlModalReload');
    var backdrop = document.getElementById('crawlModalBackdrop');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', function () {
        if (!actions.hidden) closeModal();
    });
    if (reloadBtn) reloadBtn.addEventListener('click', function () { window.location.reload(); });

    if (cooldownUntil) startCooldown(cooldownUntil);
})();
</script>
@endsection
