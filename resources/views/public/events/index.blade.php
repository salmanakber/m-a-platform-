@extends('layouts.public')

@section('title', 'Events & Veranstaltungen')
@section('meta_description', 'Workshops, Vorträge und Fachveranstaltungen rund um M&A und Unternehmensnachfolge in der Schweiz.')

@section('content')
@php
    $typeLabels = [
        'workshop' => 'Workshop',
        'presentation' => 'Vortrag',
        'hearing' => 'Hearing',
        'other' => 'Sonstiges',
    ];
@endphp
<div class="ev-page">
    <header class="ev-hero">
        <div class="container ev-hero__inner">
            <p class="ev-hero__kicker">Schweiz · Fachveranstaltungen</p>
            <h1>Events</h1>
            <p class="ev-hero__lead">Workshops, Vorträge und Hearings von M&amp;A- und Nachfolge-Experten — auch auf der Karte sichtbar.</p>
            <div class="ev-hero__actions">
                <a class="btn btn--small" href="{{ route('map.index', ['events' => 1]) }}">Auf der Karte zeigen</a>
                <a class="btn btn--ghost btn--small" href="{{ route('directory.index') }}">Expertenverzeichnis</a>
            </div>
        </div>
    </header>

    <div class="container ev-body">
        <form method="get" class="ev-filters" action="{{ route('events.index') }}">
            <div class="ev-filters__chips" role="group" aria-label="Event-Typ">
                <label class="ev-chip {{ $type === '' ? 'is-on' : '' }}">
                    <input type="radio" name="typ" value="" @checked($type === '') onchange="this.form.submit()"> Alle
                </label>
                @foreach ($typeLabels as $value => $label)
                    <label class="ev-chip {{ $type === $value ? 'is-on' : '' }}">
                        <input type="radio" name="typ" value="{{ $value }}" @checked($type === $value) onchange="this.form.submit()"> {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="ev-filters__count">{{ $events->total() }} Veranstaltung{{ $events->total() === 1 ? '' : 'en' }}</p>
        </form>

        @if ($events->isEmpty())
            <div class="ev-empty">
                <strong>Noch keine Events</strong>
                <p>Sobald Experten Veranstaltungen publizieren, erscheinen sie hier und auf der Karte.</p>
            </div>
        @else
            <div class="ev-grid">
                @foreach ($events as $event)
                    <a class="ev-card" href="{{ route('events.show', $event->slug) }}">
                        <div class="ev-card__date" aria-hidden="true">
                            @if ($event->starts_on)
                                <span class="ev-card__day">{{ $event->starts_on->format('d') }}</span>
                                <span class="ev-card__mon">{{ $event->starts_on->translatedFormat('M') }}</span>
                            @else
                                <span class="ev-card__day">?</span>
                                <span class="ev-card__mon">offen</span>
                            @endif
                        </div>
                        <div class="ev-card__body">
                            <div class="ev-card__meta">
                                <span>{{ $typeLabels[$event->event_type] ?? ucfirst($event->event_type) }}</span>
                                @if ($event->starts_at_time)
                                    <span>{{ \Illuminate\Support\Str::of($event->starts_at_time)->substr(0, 5) }} Uhr</span>
                                @endif
                                @if ($event->canton)
                                    <span>{{ $event->canton->code }}</span>
                                @endif
                            </div>
                            <h2>{{ $event->title }}</h2>
                            <p>
                                @if ($event->location_name || $event->city)
                                    {{ $event->location_name ?: $event->city }}
                                    @if ($event->city && $event->location_name) · {{ $event->city }} @endif
                                @else
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 110) }}
                                @endif
                            </p>
                            @if ($event->expert)
                                <span class="ev-card__host">{{ $event->expert->company_name }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
