@extends('layouts.expert')

@section('title', 'Events')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Events</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Inhalte</p>
            <h1>Events</h1>
            <p>Workshops, Vorträge und Veranstaltungen — manuell oder per Website-Import.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--small" href="{{ route('expert.events.create') }}">Neues Event</a>
        </div>
    </header>

    <section class="xp-panel">
        @if ($events->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Events</strong>
                <p>Erstellen Sie Ihre erste Veranstaltung oder importieren Sie Inhalte von Ihrer Website.</p>
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
                                <td><strong>{{ $event->title }}</strong>@if($event->needs_review) <span class="pill pill--warning">Prüfung</span>@endif</td>
                                <td>{{ $event->event_type }}</td>
                                <td>{{ $event->starts_on?->format('d.m.Y') ?? '—' }}</td>
                                <td>{{ $event->status }}</td>
                                <td><a class="btn btn--ghost btn--small" href="{{ route('expert.events.edit', $event) }}">Bearbeiten</a></td>
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
