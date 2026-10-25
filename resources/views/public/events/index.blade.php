@extends('layouts.public')

@section('title', 'Events & Veranstaltungen')
@section('meta_description', 'Workshops, Vorträge und Fachveranstaltungen rund um M&A und Unternehmensnachfolge in der Schweiz.')
@section('flush', true)

@section('content')
@php
    $typeLabels = [
        'workshop' => 'Workshop',
        'presentation' => 'Vortrag',
        'hearing' => 'Hearing',
        'other' => 'Sonstiges',
    ];
@endphp
<div class="nh-events-page">
    <header class="nh-page-hero nh-page-hero--cream">
        <div class="nh-page-hero__inner">
            <p class="nh-kicker">Agenda · {{ date('Y') }}</p>
            <h1>Events &amp; Veranstaltungen</h1>
            <p>Workshops, Vorträge und Hearings von M&amp;A- und Nachfolge-Experten — Anmeldung geht direkt an den Experten.</p>
            <div class="nh-page-hero__actions">
                <a class="nh-btn nh-btn--navy" href="{{ route('map.index', ['events' => 1]) }}">Auf der Karte zeigen ↗</a>
                <a class="nh-btn nh-btn--ghost nh-btn--on-light" href="{{ route('directory.index') }}">Expertenverzeichnis</a>
            </div>
        </div>
    </header>

    <div class="nh-events-page__body">
        <form method="get" class="nh-filterbar" action="{{ route('events.index') }}">
            <div class="nh-filterbar__chips" role="group" aria-label="Event-Typ">
                <label class="{{ $type === '' ? 'is-on' : '' }}">
                    <input type="radio" name="typ" value="" @checked($type === '') onchange="this.form.submit()"> Alle
                </label>
                @foreach ($typeLabels as $value => $label)
                    <label class="{{ $type === $value ? 'is-on' : '' }}">
                        <input type="radio" name="typ" value="{{ $value }}" @checked($type === $value) onchange="this.form.submit()"> {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="nh-filterbar__count">{{ $events->total() }} Veranstaltung{{ $events->total() === 1 ? '' : 'en' }}</p>
        </form>

        @if ($events->isEmpty())
            <div class="nh-empty">
                <strong>Noch keine Events</strong>
                <p>Sobald Experten Veranstaltungen publizieren, erscheinen sie hier und auf der Karte.</p>
            </div>
        @else
            <div class="nh-event-list">
                @foreach ($events as $event)
                    <article class="nh-event-row {{ !empty($event->is_top_event) ? 'is-top' : '' }}">
                        <div class="nh-event-row__date">
                            @if ($event->starts_on)
                                <b>{{ $event->starts_on->format('d') }}</b>
                                <span>{{ mb_strtoupper($event->starts_on->translatedFormat('M Y')) }}</span>
                            @else
                                <b>—</b>
                                <span>offen</span>
                            @endif
                        </div>
                        <div class="nh-event-row__body">
                            <h2><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h2>
                            <div class="nh-tags">
                                @if (!empty($event->is_top_event))
                                    <span class="is-gold">Top Event</span>
                                @endif
                                <span>{{ $typeLabels[$event->event_type] ?? ucfirst((string) $event->event_type) }}</span>
                                @if ($event->city || optional($event->canton)->code)
                                    <span>{{ $event->city ?: optional($event->canton)->code }}</span>
                                @endif
                                @if ($event->capacity_label)
                                    <span>{{ $event->capacity_label }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="nh-event-row__price">
                            {{ $event->price_label ?: 'Kostenlos' }}
                        </div>
                        <a class="nh-btn nh-btn--navy nh-btn--small" href="{{ route('events.show', $event->slug) }}#anmelden">Anmelden ↗</a>
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
