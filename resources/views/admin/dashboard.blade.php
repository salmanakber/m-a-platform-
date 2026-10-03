@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <header class="page-head">
        <p class="page-kicker">Administration</p>
        <h1 class="page-title" style="font-size:2rem;">Dashboard</h1>
        <p class="page-lead">Überblick über Registrierungen, Anfragen, Inhalte und Promotionen.</p>
    </header>

    <div class="stat-grid">
        <a class="stat-card" href="{{ route('admin.experts.index', ['status' => 'pending']) }}" style="text-decoration:none;color:inherit;">
            <span>Ausstehende Registrierungen</span>
            <strong>{{ $pendingExperts }}</strong>
        </a>
        <a class="stat-card" href="{{ route('admin.experts.index', ['status' => 'approved']) }}" style="text-decoration:none;color:inherit;">
            <span>Freigeschaltete Experten</span>
            <strong>{{ $approvedExperts }}</strong>
        </a>
        <a class="stat-card" href="{{ route('admin.leads.index', ['status' => 'new']) }}" style="text-decoration:none;color:inherit;">
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

    <p class="action-bar" style="border:none;padding-left:0;">
        <a class="btn" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">Registrierungen prüfen</a>
        <a class="btn btn--ghost" href="{{ route('admin.leads.index') }}">Anfragen</a>
        <a class="btn btn--ghost" href="{{ route('admin.crawl.index') }}">Crawl starten</a>
        <a class="btn btn--ghost" href="{{ route('admin.settings.edit') }}">Einstellungen</a>
    </p>

    <div class="profile-shell" style="margin-top:1.5rem;">
        <section class="surface">
            <h2 style="margin-top:0;font-family:var(--font-display);font-size:1.35rem;font-weight:500;">Neue Registrierungen</h2>
            @if ($recentPendingExperts->isEmpty())
                <p style="color:var(--stone);margin:0;">Keine ausstehenden Profile.</p>
            @else
                <table class="data-table">
                    <thead><tr><th>Firma</th><th>Datum</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentPendingExperts as $expert)
                            <tr>
                                <td><strong>{{ $expert->company_name }}</strong><br><span style="color:var(--stone);font-size:0.85rem;">{{ $expert->email }}</span></td>
                                <td>{{ optional($expert->created_at)->format('d.m.Y H:i') }}</td>
                                <td><a href="{{ route('admin.experts.show', $expert) }}">Prüfen →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="surface">
            <h2 style="margin-top:0;font-family:var(--font-display);font-size:1.35rem;font-weight:500;">Neueste Anfragen</h2>
            @if ($recentLeads->isEmpty())
                <p style="color:var(--stone);margin:0;">Noch keine Anfragen.</p>
            @else
                <table class="data-table">
                    <thead><tr><th>Interessent</th><th>Experte</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentLeads as $lead)
                            <tr>
                                <td>
                                    <strong>{{ $lead->owner_name }}</strong>
                                    <br><span class="badge">{{ $lead->status }}</span>
                                </td>
                                <td>{{ $lead->expert->company_name ?? '—' }}</td>
                                <td><a href="{{ route('admin.leads.show', $lead) }}">Öffnen →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
