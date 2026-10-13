@extends('layouts.expert')

@section('title', 'Events')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Events</span>
@endsection

@section('content')
@php
    $statusLabel = ['published' => 'Veröffentlicht', 'draft' => 'Entwurf', 'blocked' => 'Gesperrt'];
    $pill = ['published' => 'success', 'draft' => 'warning', 'blocked' => 'danger'];
@endphp
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Inhalte</p>
            <h1>Events</h1>
            <p>Workshops, Vorträge und Veranstaltungen — manuell anlegen oder von der Website als Entwurf übernehmen.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--small" href="{{ route('expert.events.create') }}">Event manuell erstellen</a>
            <a class="btn btn--ghost btn--small" href="{{ route('expert.crawl.index') }}">Website scannen</a>
        </div>
    </header>

    <div class="xp-action-strip">
        <div>
            <strong>Manuelle Erfassung ist Standard</strong>
            <p>Importierte Events bleiben Entwürfe, bis Sie sie prüfen und freigeben.</p>
        </div>
        <a class="btn btn--small" href="{{ route('expert.events.create') }}">Neues Event</a>
    </div>

    <section class="xp-panel">
        @if ($events->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Events</strong>
                <p>Erstellen Sie Ihre erste Veranstaltung manuell oder scannen Sie Ihre Website.</p>
                <p style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <a class="btn btn--small" href="{{ route('expert.events.create') }}">Event erstellen</a>
                    <a class="btn btn--ghost btn--small" href="{{ route('expert.crawl.index') }}">Website-Import</a>
                </p>
            </div>
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
                                    @if($event->needs_review)
                                        <span class="pill pill--warning">Prüfung</span>
                                    @endif
                                    @if($event->is_crawled)
                                        <span class="pill pill--muted">Import</span>
                                    @endif
                                </td>
                                <td>{{ $event->event_type }}</td>
                                <td>{{ $event->starts_on?->format('d.m.Y') ?? '—' }}</td>
                                <td><span class="pill pill--{{ $pill[$event->status] ?? 'muted' }}">{{ $statusLabel[$event->status] ?? $event->status }}</span></td>
                                <td><a class="btn btn--ghost btn--small" href="{{ route('expert.events.edit', $event) }}">{{ $event->status === 'draft' ? 'Prüfen' : 'Bearbeiten' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $events->links() }}</div>
        @endif
    </section>
</div>
@endsection
