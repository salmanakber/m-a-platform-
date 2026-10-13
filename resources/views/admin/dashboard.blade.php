@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Dashboard</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Administration</p>
            <h1>Dashboard</h1>
            <p>Überblick über Registrierungen, Anfragen, Inhalte und Promotionen.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--small" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">Registrierungen</a>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.import.index') }}">Import</a>
        </div>
    </header>

    <div class="stat-grid">
        <a class="stat-card" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">
            <span>Ausstehende Registrierungen</span>
            <strong>{{ $pendingExperts }}</strong>
        </a>
        <a class="stat-card" href="{{ route('admin.experts.index', ['status' => 'approved']) }}">
            <span>Freigeschaltete Experten</span>
            <strong>{{ $approvedExperts }}</strong>
        </a>
        <a class="stat-card" href="{{ route('admin.leads.index', ['status' => 'new']) }}">
            <span>Offene Anfragen</span>
            <strong>{{ $openLeads }}</strong>
        </a>
        <div class="stat-card"><span>Anfragen gesamt</span><strong>{{ $totalLeads }}</strong></div>
        <div class="stat-card"><span>Aktive Promotionen</span><strong>{{ $activePromotions }}</strong></div>
        <div class="stat-card"><span>Ausstehende Promotionen</span><strong>{{ $pendingPromotions }}</strong></div>
        <div class="stat-card"><span>Veröffentlichte Artikel</span><strong>{{ $publishedArticles }}</strong></div>
        <div class="stat-card"><span>Gesperrte Artikel</span><strong>{{ $blockedArticles }}</strong></div>
        <div class="stat-card"><span>Offene Rechnungen</span><strong>{{ $unpaidInvoices }}</strong></div>
        <div class="stat-card"><span>Deaktivierte Experten</span><strong>{{ $disabledExperts }}</strong></div>
        <div class="stat-card"><span>KI-Vorschläge offen</span><strong>{{ $pendingAiSuggestions }}</strong></div>
        <div class="stat-card"><span>Crawl-Jobs heute</span><strong>{{ $crawlJobsToday }}</strong></div>
    </div>

    <div class="admin-dash-split">
        <section class="sheet">
            <div class="sheet__head"><h2>Neue Registrierungen</h2></div>
            <div class="sheet__body">
                @if ($recentPendingExperts->isEmpty())
                    <p class="xp-muted" style="margin:0;">Keine ausstehenden Profile.</p>
                @else
                    <table class="entity-table">
                        <thead><tr><th>Firma</th><th>Datum</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($recentPendingExperts as $expert)
                                <tr>
                                    <td>
                                        <p class="entity-name">{{ $expert->company_name }}</p>
                                        <p class="entity-sub">{{ $expert->email }}</p>
                                    </td>
                                    <td>{{ optional($expert->created_at)->format('d.m.Y H:i') }}</td>
                                    <td><a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}">Prüfen</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

        <section class="sheet">
            <div class="sheet__head"><h2>Neueste Anfragen</h2></div>
            <div class="sheet__body">
                @if ($recentLeads->isEmpty())
                    <p class="xp-muted" style="margin:0;">Noch keine Anfragen.</p>
                @else
                    <table class="entity-table">
                        <thead><tr><th>Interessent</th><th>Experte</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($recentLeads as $lead)
                                <tr>
                                    <td>
                                        <p class="entity-name">{{ $lead->owner_name }}</p>
                                        <span class="pill pill--muted">{{ $lead->status }}</span>
                                    </td>
                                    <td>{{ $lead->expert->company_name ?? '—' }}</td>
                                    <td><a class="btn btn--ghost btn--small" href="{{ route('admin.leads.show', $lead) }}">Öffnen</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
