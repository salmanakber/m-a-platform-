@extends('layouts.admin')

@section('title', $expert->company_name)
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.index') }}">Experten</a>
    <span>/</span>
    <span>{{ $expert->company_name }}</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
        'disabled' => 'Deaktiviert',
    ];
    $pill = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disabled' => 'muted',
    ];
    $groupLabel = ($professionalGroups ?? [])[$expert->professional_group] ?? ($expert->professional_group ?: '—');
    $contact = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));
@endphp

<div class="ax-hub">
    <header class="ax-hero">
        <div class="ax-hero__main">
            <div class="ax-hero__logo">
                @if ($expert->logo_path)
                    <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                @else
                    <span>{{ mb_strtoupper(mb_substr($expert->company_name, 0, 2)) }}</span>
                @endif
            </div>
            <div>
                <div class="ax-hero__meta">
                    <span class="pill pill--{{ $pill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span>
                    <span class="ax-chip">{{ $groupLabel }}</span>
                    @if ($expert->is_public)<span class="ax-chip ax-chip--ok">Öffentlich</span>@endif
                </div>
                <h1>{{ $expert->company_name }}</h1>
                <p>
                    @if ($contact){{ $contact }} · @endif
                    <a href="mailto:{{ $expert->email }}">{{ $expert->email }}</a>
                    @if ($expert->phone) · {{ $expert->phone }}@endif
                </p>
            </div>
        </div>
        <div class="ax-hero__actions">
            <a href="{{ route('admin.experts.edit', $expert) }}" class="btn btn--small">Profil bearbeiten</a>
            @if (in_array($expert->status, ['pending', 'rejected', 'disabled'], true))
                <form action="{{ route('admin.experts.approve', $expert) }}" method="post">@csrf<button type="submit" class="btn btn--small">{{ $expert->status === 'pending' ? 'Freigeben' : 'Wieder freigeben' }}</button></form>
            @endif
            @if ($expert->status === 'pending')
                <form action="{{ route('admin.experts.reject', $expert) }}" method="post" onsubmit="return confirm('Experte wirklich ablehnen?');">
                    @csrf
                    <input type="hidden" name="rejection_reason" value="Profil unvollständig oder nicht passend.">
                    <button type="submit" class="btn btn--ghost btn--small">Ablehnen</button>
                </form>
            @endif
            @if ($expert->status === 'approved')
                <form action="{{ route('admin.experts.deactivate', $expert) }}" method="post">@csrf<button type="submit" class="btn btn--ghost btn--small">Deaktivieren</button></form>
            @endif
            @if ($expert->is_public && $expert->status === 'approved')
                <a href="{{ route('experts.show', $expert->slug) }}" class="btn btn--ghost btn--small" target="_blank" rel="noopener">Öffentliches Profil ↗</a>
            @endif
        </div>
    </header>

    <nav class="ax-tabs" aria-label="Bereiche" data-ax-tabs>
        <a href="#uebersicht" data-ax-tab="uebersicht" class="is-active">Übersicht</a>
        <a href="#crawl" data-ax-tab="crawl">Crawl</a>
        <a href="#ki" data-ax-tab="ki">KI-Vorschläge <em>{{ $aiSuggestions->count() }}</em></a>
        <a href="#artikel" data-ax-tab="artikel">Fachbeiträge <em>{{ $articles->count() }}</em></a>
        <a href="#events" data-ax-tab="events">Events <em>{{ $events->count() }}</em></a>
        <a href="#zugang" data-ax-tab="zugang">Zugang</a>
    </nav>

    <div class="ax-panel is-active" data-ax-panel="uebersicht" id="uebersicht">
    <div class="ax-grid">
        <section class="ax-card">
            <header class="ax-card__head"><h2>Stammdaten</h2></header>
            <dl class="ax-meta">
                <div><dt>Website</dt><dd>@if($expert->website)<a href="{{ $expert->website }}" target="_blank" rel="noopener">{{ $expert->website }}</a>@else — @endif</dd></div>
                <div><dt>Fokus</dt><dd>{{ collect([($expert->offers_buy ? 'Kauf' : null), ($expert->offers_sell ? 'Verkauf' : null)])->filter()->implode(' · ') ?: '—' }}</dd></div>
                <div><dt>Slug</dt><dd><code>{{ $expert->slug }}</code></dd></div>
                <div><dt>Registriert</dt><dd>{{ optional($expert->created_at)->format('d.m.Y H:i') }}</dd></div>
            </dl>
            @if ($expert->description)
                <div class="ax-prose">
                    <h3>Beschreibung</h3>
                    <p>{{ $expert->description }}</p>
                </div>
            @endif
            @if ($expert->services_text)
                <div class="ax-prose">
                    <h3>Leistungen</h3>
                    <p>{{ $expert->services_text }}</p>
                </div>
            @endif
            @if ($expert->rejection_reason)
                <div class="ax-alert">Ablehnungsgrund: {{ $expert->rejection_reason }}</div>
            @endif
        </section>

        <section class="ax-card">
            <header class="ax-card__head"><h2>Standorte</h2><a href="{{ route('admin.experts.edit', $expert) }}#standorte">Bearbeiten</a></header>
            @if ($expert->offices->isEmpty())
                <p class="ax-empty">Keine Standorte hinterlegt.</p>
            @else
                <ul class="ax-list">
                    @foreach ($expert->offices as $office)
                        <li>
                            <strong>{{ $office->is_primary ? 'Hauptsitz' : ($office->label ?: 'Standort') }}</strong>
                            <span>{{ $office->address_line }}, {{ $office->postal_code }} {{ $office->city }}@if($office->canton) · {{ $office->canton->code }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
    </div>

    {{-- Crawl --}}
    <div class="ax-panel" data-ax-panel="crawl" id="crawl" hidden>
    <section class="ax-card ax-card--wide">
        <header class="ax-card__head">
            <div>
                <h2>Website-Crawl</h2>
                <p>Importiert Blog-Artikel und Events von der Experten-Website — inkl. optionaler KI-Nachbearbeitung.</p>
            </div>
        </header>
        <form method="post" action="{{ route('admin.experts.crawl.update', $expert) }}" class="ax-crawl">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="website_crawl_url">Crawl-URL</label>
                <input class="form-control" type="url" name="website_crawl_url" id="website_crawl_url" value="{{ old('website_crawl_url', $expert->website_crawl_url ?: $expert->website) }}" placeholder="https://…">
            </div>
            <div class="ax-checks">
                <label><input type="checkbox" name="crawl_enabled" value="1" @checked(old('crawl_enabled', $expert->crawl_enabled))> Crawl aktiv</label>
                <label><input type="checkbox" name="crawl_import_articles" value="1" @checked(old('crawl_import_articles', $expert->crawl_import_articles ?? true))> Artikel importieren</label>
                <label><input type="checkbox" name="crawl_import_events" value="1" @checked(old('crawl_import_events', $expert->crawl_import_events ?? true))> Events importieren</label>
                <label><input type="checkbox" name="crawl_use_ai" value="1" @checked(old('crawl_use_ai', $expert->crawl_use_ai))> KI für Verbesserungen nutzen</label>
            </div>
            <div class="ax-crawl__actions">
                <button type="submit" class="btn btn--small">Einstellungen speichern</button>
            </div>
        </form>
        <div class="ax-crawl-run" id="adminCrawlRun"
             data-run-url="{{ route('admin.experts.crawl.run', $expert) }}"
             data-articles-url="{{ route('admin.experts.articles.create', $expert) }}"
             data-events-url="{{ route('admin.experts.events.create', $expert) }}">
            <button type="button" class="btn" id="adminCrawlStartBtn">Crawl jetzt starten</button>
            <span class="field-hint">Live-Fortschritt im Popup — neue Entwürfe erscheinen unter Fachbeiträge / Events.</span>
        </div>
        @if ($crawlJobs->isNotEmpty())
            <div class="ax-jobs">
                <h3>Letzte Läufe</h3>
                <ul>
                    @foreach ($crawlJobs as $job)
                        <li>
                            <b>{{ optional($job->created_at)->format('d.m.Y H:i') }}</b>
                            <span class="pill pill--{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">{{ $job->status }}</span>
                            <em>{{ $job->notes ?: ($job->progress_label ?: '—') }}</em>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
    </div>

    {{-- AI --}}
    <div class="ax-panel" data-ax-panel="ki" id="ki" hidden>
    <section class="ax-card ax-card--wide">
        <header class="ax-card__head">
            <div>
                <h2>KI-Vorschläge</h2>
                <p>Offene Vorschläge für dieses Profil anwenden oder ablehnen.</p>
            </div>
            <a href="{{ route('admin.ai-suggestions.index') }}">Alle Vorschläge</a>
        </header>
        @if ($aiSuggestions->isEmpty())
            <p class="ax-empty">Keine offenen KI-Vorschläge. Starten Sie einen Crawl mit aktivierter KI oder prüfen Sie die globale Liste.</p>
        @else
            <div class="ax-ai-list">
                @foreach ($aiSuggestions as $suggestion)
                    <article class="ax-ai-item">
                        <div>
                            <strong>{{ $suggestion->field_name }}</strong>
                            <p class="ax-ai-item__current"><span>Aktuell</span>{{ \Illuminate\Support\Str::limit((string) $suggestion->current_value, 160) ?: '—' }}</p>
                            <p class="ax-ai-item__suggested"><span>Vorschlag</span>{{ \Illuminate\Support\Str::limit((string) $suggestion->suggested_value, 220) }}</p>
                        </div>
                        <div class="ax-ai-item__actions">
                            <form method="post" action="{{ route('admin.ai-suggestions.apply', $suggestion) }}">@csrf<button class="btn btn--small" type="submit">Anwenden</button></form>
                            <form method="post" action="{{ route('admin.ai-suggestions.reject', $suggestion) }}">@csrf<button class="btn btn--ghost btn--small" type="submit">Ablehnen</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    </div>

    {{-- Articles --}}
    <div class="ax-panel" data-ax-panel="artikel" id="artikel" hidden>
    <section class="ax-card ax-card--wide">
        <header class="ax-card__head">
            <div>
                <h2>Fachbeiträge</h2>
                <p>Im Namen dieses Experten erstellen, mit KI entwerfen oder bearbeiten.</p>
            </div>
            <a class="btn btn--small" href="{{ route('admin.experts.articles.create', $expert) }}">+ Beitrag</a>
        </header>
        @if ($articles->isEmpty())
            <p class="ax-empty">Noch keine Fachbeiträge. Legen Sie einen an oder starten Sie einen Crawl.</p>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td>
                                    <strong>{{ $article->title }}</strong>
                                    @if ($article->category)<div class="entity-sub">{{ $article->category->name_de }}</div>@endif
                                </td>
                                <td>{{ $article->status }}</td>
                                <td>{{ optional($article->published_at ?: $article->created_at)->format('d.m.Y') }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.articles.edit', [$expert, $article]) }}">Bearbeiten</a>
                                        <form method="post" action="{{ route('admin.experts.articles.destroy', [$expert, $article]) }}" onsubmit="return confirm('Beitrag löschen?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div>

    {{-- Events --}}
    <div class="ax-panel" data-ax-panel="events" id="events" hidden>
    <section class="ax-card ax-card--wide">
        <header class="ax-card__head">
            <div>
                <h2>Events</h2>
                <p>Events für diesen Experten anlegen (inkl. KI-Entwurf) — wie im Expertenbereich.</p>
            </div>
            <a class="btn btn--small" href="{{ route('admin.experts.events.create', $expert) }}">+ Event</a>
        </header>
        @if ($events->isEmpty())
            <p class="ax-empty">Noch keine Events.</p>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Typ</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>
                                    <strong>{{ $event->title }}</strong>
                                    @if ($event->is_top_event)<div class="entity-sub">Top Event</div>@endif
                                </td>
                                <td>{{ $event->event_type }}</td>
                                <td>{{ optional($event->starts_on)->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $event->status }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.events.edit', [$expert, $event]) }}">Bearbeiten</a>
                                        <form method="post" action="{{ route('admin.experts.events.destroy', [$expert, $event]) }}" onsubmit="return confirm('Event löschen?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div>

    {{-- Access --}}
    <div class="ax-panel" data-ax-panel="zugang" id="zugang" hidden>
    <section class="ax-card ax-card--wide">
        <header class="ax-card__head"><h2>Login-Zugang</h2></header>
        @php
            $loginUser = $expert->user;
            $isImportLogin = $loginUser && mb_strtolower((string) $loginUser->email) === 'import@nachfolge-experten.local';
        @endphp
        <div class="ax-access">
            @if ($isImportLogin || ! $loginUser)
                <p>Noch kein persönliches Login — unter Bearbeiten E-Mail/Passwort setzen oder Reset-Link senden.</p>
            @else
                <dl class="ax-meta">
                    <div><dt>Login</dt><dd>{{ $loginUser->email }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $loginUser->is_active ? 'Aktiv' : 'Deaktiviert' }}</dd></div>
                </dl>
            @endif
            <div class="ax-access__actions">
                <a class="btn btn--small" href="{{ route('admin.experts.edit', $expert) }}#zugang">E-Mail / Passwort setzen</a>
                <form method="post" action="{{ route('admin.experts.password-reset', $expert) }}" onsubmit="return confirm('Reset-Link an {{ $expert->email }} senden?');">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--small">Passwort-Reset senden</button>
                </form>
                <a class="btn btn--ghost btn--small" href="{{ route('admin.leads.index', ['expert_id' => $expert->id]) }}">Anfragen ansehen</a>
                <form action="{{ route('admin.experts.destroy', $expert) }}" method="post" onsubmit="return confirm('Experte «{{ $expert->company_name }}» wirklich löschen?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--ghost btn--small" style="color:#b42318;">Experte löschen</button>
                </form>
            </div>
        </div>
    </section>
    </div>
</div>

<script>
(function () {
    // Tab panels — show only the active section
    var tabs = document.querySelector('[data-ax-tabs]');
    if (tabs) {
        var links = Array.prototype.slice.call(tabs.querySelectorAll('[data-ax-tab]'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('[data-ax-panel]'));
        function showTab(id) {
            links.forEach(function (a) {
                a.classList.toggle('is-active', a.getAttribute('data-ax-tab') === id);
            });
            panels.forEach(function (p) {
                var on = p.getAttribute('data-ax-panel') === id;
                p.classList.toggle('is-active', on);
                p.hidden = !on;
            });
            try { history.replaceState(null, '', '#' + id); } catch (e) {}
        }
        links.forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                showTab(a.getAttribute('data-ax-tab'));
            });
        });
        var hash = (location.hash || '#uebersicht').replace('#', '');
        if (!document.querySelector('[data-ax-panel="'+hash+'"]')) hash = 'uebersicht';
        showTab(hash);
    }

    var wrap = document.getElementById('adminCrawlRun');
    var btn = document.getElementById('adminCrawlStartBtn');
    if (!wrap || !btn) return;

    var pollTimer = null;
    var lastStep = '';
    var lastLabel = '';
    var finishedShown = false;
    var seenEventIds = {};
    var CRAWL_STEPS = [
        { id: 'queued', label: 'Warteschlange' },
        { id: 'discover', label: 'Website lesen' },
        { id: 'sitemap', label: 'Sitemap & Feeds' },
        { id: 'fetch', label: 'Seiten laden' },
        { id: 'finalize', label: 'Ergebnisse speichern' }
    ];
    var STEP_MAP = {
        queued: 'queued', discover: 'discover', sitemap: 'sitemap', found: 'sitemap',
        fetch: 'fetch', import: 'fetch', skip: 'fetch', finalize: 'finalize'
    };

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function openLive() {
        finishedShown = false;
        lastStep = '';
        lastLabel = '';
        seenEventIds = {};
        LiveProgress.open({
            title: 'Website-Crawl',
            subtitle: 'Live: echte URLs, Importe und Überspringungen',
            steps: CRAWL_STEPS,
            startLog: 'Crawl wird gestartet…'
        });
        LiveProgress.setStats({ found: 0, loaded: 0, drafts: 0 });
        LiveProgress.activate('queued');
        LiveProgress.setPercent(3);
    }

    function applyStatus(data) {
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
            LiveProgress.log(lbl, raw === 'import' ? 'ok' : (raw === 'skip' ? 'info' : 'working'));
            lastLabel = lbl;
        }
        (data.events || []).forEach(function (ev) {
            if (!ev || seenEventIds[ev.id]) return;
            seenEventIds[ev.id] = true;
            LiveProgress.log(ev.text, ev.kind || 'info');
        });
        if (data.finished && !finishedShown) {
            finishedShown = true;
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = null;
            CRAWL_STEPS.forEach(function (s) { LiveProgress.done(s.id); });
            var ok = data.status === 'completed';
            if (data.notes) LiveProgress.log(data.notes, ok ? 'ok' : 'error');
            LiveProgress.finish({
                ok: ok,
                title: ok ? 'Crawl fertig' : 'Fehler',
                message: data.notes || (ok ? 'Abgeschlossen.' : 'Fehlgeschlagen.'),
                actionsHtml:
                    '<a class="btn btn--small" href="' + wrap.getAttribute('data-articles-url') + '">Artikel</a>' +
                    '<a class="btn btn--small" href="' + wrap.getAttribute('data-events-url') + '">Events</a>' +
                    '<button type="button" class="btn btn--ghost btn--small" data-lp-reload>Aktualisieren</button>' +
                    '<button type="button" class="btn btn--ghost btn--small" data-lp-close>Schliessen</button>'
            });
        }
    }

    function poll(url) {
        if (pollTimer) clearInterval(pollTimer);
        function tick() {
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); }).then(applyStatus).catch(function () {});
        }
        tick();
        pollTimer = setInterval(tick, 320);
    }

    function fireProcess(url) {
        fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    btn.addEventListener('click', function () {
        if (!window.LiveProgress) {
            alert('Fortschrittsanzeige nicht geladen — bitte Seite neu laden.');
            return;
        }
        var urlInput = document.getElementById('website_crawl_url');
        var url = (urlInput && urlInput.value || '').trim();
        if (!url) {
            alert('Bitte eine Crawl-URL hinterlegen.');
            return;
        }
        openLive();
        btn.disabled = true;

        fetch(wrap.getAttribute('data-run-url'), {
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
            return res.json().then(function (data) { return { ok: res.ok, status: res.status, data: data }; });
        }).then(function (result) {
            if (result.status === 409 && result.data.status_url) {
                LiveProgress.log('Läuft bereits — Fortschritt wird geladen…', 'working');
                if (result.data.process_url) fireProcess(result.data.process_url);
                poll(result.data.status_url);
                return;
            }
            if (!result.ok) throw new Error((result.data && result.data.message) || 'Start fehlgeschlagen.');
            LiveProgress.log('Job gestartet', 'ok');
            if (result.data.process_url) fireProcess(result.data.process_url);
            if (result.data.status_url) poll(result.data.status_url);
        }).catch(function (err) {
            LiveProgress.finish({ ok: false, title: 'Fehler', message: err.message || 'Fehler' });
        }).finally(function () {
            btn.disabled = false;
        });
    });
})();
</script>
@endsection
