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

    <nav class="ax-tabs" aria-label="Bereiche">
        <a href="#uebersicht">Übersicht</a>
        <a href="#crawl">Crawl</a>
        <a href="#ki">KI-Vorschläge <em>{{ $aiSuggestions->count() }}</em></a>
        <a href="#artikel">Fachbeiträge <em>{{ $articles->count() }}</em></a>
        <a href="#events">Events <em>{{ $events->count() }}</em></a>
        <a href="#zugang">Zugang</a>
    </nav>

    <div class="ax-grid" id="uebersicht">
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

    {{-- Crawl --}}
    <section class="ax-card ax-card--wide" id="crawl">
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
        <form method="post" action="{{ route('admin.experts.crawl.run', $expert) }}" class="ax-crawl-run" onsubmit="return confirm('Crawl jetzt starten? Kann einige Minuten dauern.');">
            @csrf
            <button type="submit" class="btn">Crawl jetzt starten</button>
            <span class="field-hint">Läuft synchron (ohne SSH-Worker). Danach erscheinen neue Entwürfe unter Fachbeiträge / Events.</span>
        </form>
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

    {{-- AI --}}
    <section class="ax-card ax-card--wide" id="ki">
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

    {{-- Articles --}}
    <section class="ax-card ax-card--wide" id="artikel">
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

    {{-- Events --}}
    <section class="ax-card ax-card--wide" id="events">
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

    {{-- Access --}}
    <section class="ax-card ax-card--wide" id="zugang">
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
@endsection
