@extends('layouts.expert')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <span>Dashboard</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
        'disabled' => 'Deaktiviert',
    ];
    $statusPill = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disabled' => 'muted',
    ];
    $leadLabel = [
        'new' => 'Neu',
        'contacted' => 'Kontaktiert',
        'in_progress' => 'In Bearbeitung',
        'closed' => 'Abgeschlossen',
    ];
@endphp

<div class="xp-dash">
    <header class="xp-hero">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Expertenbereich</p>
            <h1>{{ $expert->company_name ?? 'Willkommen' }}</h1>
            <p>
                Steuern Sie Anfragen, Sichtbarkeit und Inhalte an einem Ort.
                @if ($expert)
                    <span class="pill pill--{{ $statusPill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span>
                    @if ($expert->is_public)
                        <span class="pill pill--info">Öffentlich</span>
                    @endif
                @endif
            </p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--small" href="{{ route('expert.leads.index') }}">Anfragen</a>
            <a class="btn btn--ghost btn--small" href="{{ route('expert.profile.edit') }}">Profil bearbeiten</a>
        </div>
    </header>

    <div class="xp-stat-grid">
        <a class="xp-stat" href="{{ route('expert.leads.index') }}">
            <span>Neue Anfragen</span>
            <strong>{{ $newLeads }}</strong>
            <em>{{ $totalLeads }} gesamt</em>
        </a>
        <a class="xp-stat" href="{{ route('expert.articles.index') }}">
            <span>Artikel</span>
            <strong>{{ $articleCount }}</strong>
            <em>Wissensbeiträge</em>
        </a>
        <a class="xp-stat" href="{{ route('expert.promotions.index') }}">
            <span>Promotionen</span>
            <strong>{{ $activePromotions }}</strong>
            <em>{{ $pendingPromotions }} ausstehend</em>
        </a>
        <a class="xp-stat" href="{{ route('expert.offices.index') }}">
            <span>Standorte</span>
            <strong>{{ $officeCount }}</strong>
            <em>Büros hinterlegt</em>
        </a>
    </div>

    <div class="xp-grid">
        <section class="xp-panel">
            <div class="xp-panel__head">
                <div>
                    <h2>Aktuelle Anfragen</h2>
                    <p>Die neuesten Lead-Eingänge aus dem Verzeichnis.</p>
                </div>
                <a href="{{ route('expert.leads.index') }}">Alle anzeigen</a>
            </div>
            @if ($recentLeads->isEmpty())
                <div class="xp-empty">
                    <strong>Noch keine Anfragen</strong>
                    <p>Sobald Interessenten Sie kontaktieren, erscheinen sie hier.</p>
                </div>
            @else
                <ul class="xp-lead-list">
                    @foreach ($recentLeads as $lead)
                        <li>
                            <div>
                                <strong>{{ $lead->owner_name }}</strong>
                                <span>{{ $lead->created_at->format('d.m.Y H:i') }} · {{ $lead->email }}</span>
                            </div>
                            <div class="xp-lead-list__side">
                                <span class="pill pill--{{ $lead->status === 'new' ? 'warning' : 'muted' }}">{{ $leadLabel[$lead->status] ?? $lead->status }}</span>
                                <a href="{{ route('expert.leads.show', $lead) }}">Öffnen</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="xp-side">
            <section class="xp-panel">
                <div class="xp-panel__head">
                    <div>
                        <h2>Profilvollständigkeit</h2>
                        <p>{{ $completeness['done'] }} von {{ $completeness['total'] }} Punkten</p>
                    </div>
                    <strong class="xp-percent">{{ $completeness['percent'] }}%</strong>
                </div>
                <div class="xp-progress" aria-hidden="true">
                    <span style="width: {{ $completeness['percent'] }}%"></span>
                </div>
                @if (!empty($completeness['missing']))
                    <ul class="xp-missing">
                        @foreach (array_slice($completeness['missing'], 0, 4) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <a class="btn btn--ghost btn--small" href="{{ route('expert.profile.edit') }}">Jetzt ergänzen</a>
                @else
                    <p class="xp-ok">Profil ist vollständig — starke Grundlage für Anfragen.</p>
                @endif
            </section>

            <section class="xp-panel">
                <div class="xp-panel__head">
                    <div>
                        <h2>Website-Import</h2>
                        <p>Beiträge von Ihrer Website übernehmen.</p>
                    </div>
                </div>
                @if ($expert && $expert->crawl_enabled)
                    <p class="xp-ok">
                        Import aktiv
                        @if ($expert->website_crawl_url)
                            · {{ \Illuminate\Support\Str::limit($expert->website_crawl_url, 36) }}
                        @endif
                    </p>
                @else
                    <p class="xp-muted">Noch nicht aktiviert — verbinden Sie Ihre Website, um Inhalte zu übernehmen.</p>
                @endif
                @if ($lastCrawl)
                    @php
                        $crawlStatusLabel = [
                            'pending' => 'Wartend',
                            'running' => 'Läuft',
                            'completed' => 'Abgeschlossen',
                            'failed' => 'Fehlgeschlagen',
                        ];
                    @endphp
                    <p class="xp-muted">Letzter Lauf: {{ $lastCrawl->created_at->format('d.m.Y H:i') }} · {{ $crawlStatusLabel[$lastCrawl->status] ?? $lastCrawl->status }}</p>
                @endif
                <a class="btn btn--small" href="{{ route('expert.crawl.index') }}">Import öffnen</a>
            </section>

            <section class="xp-panel">
                <div class="xp-panel__head">
                    <div>
                        <h2>Schnellaktionen</h2>
                    </div>
                </div>
                <div class="xp-actions">
                    <a href="{{ route('expert.articles.create') }}">Artikel schreiben</a>
                    <a href="{{ route('expert.promotions.index') }}">Promotion buchen</a>
                    <a href="{{ route('expert.offices.index') }}">Standort hinzufügen</a>
                    @if ($expert?->is_public && $expert?->status === 'approved')
                        <a href="{{ route('experts.show', $expert->slug) }}" target="_blank" rel="noopener">Öffentliches Profil</a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
