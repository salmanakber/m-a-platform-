@extends('layouts.public')

@section('title', 'Events & Veranstaltungen')
@section('meta_description', 'Workshops, Vorträge und Fachveranstaltungen rund um M&A und Unternehmensnachfolge in der Schweiz.')

@section('content')
<div class="blog-page">
    <header class="blog-hero">
        <div class="container blog-hero__inner">
            <p class="blog-hero__kicker">Veranstaltungen</p>
            <h1>Events</h1>
            <p class="blog-hero__lead">Workshops, Vorträge und Hearings von Schweizer M&amp;A- und Nachfolge-Experten.</p>
        </div>
    </header>

    <div class="container blog-page__body">
        <form method="get" class="form-row" style="margin-bottom:1.25rem;max-width:320px;">
            <div class="form-group" style="margin:0;">
                <label for="typ">Typ</label>
                <select class="form-control" name="typ" id="typ" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <option value="workshop" @selected($type === 'workshop')>Workshop</option>
                    <option value="presentation" @selected($type === 'presentation')>Vortrag</option>
                    <option value="hearing" @selected($type === 'hearing')>Hearing</option>
                    <option value="other" @selected($type === 'other')>Sonstiges</option>
                </select>
            </div>
        </form>

        @if ($events->isEmpty())
            <div class="blog-empty">
                <strong>Noch keine Events</strong>
                <p>Sobald Experten Veranstaltungen publizieren oder importieren, erscheinen sie hier.</p>
            </div>
        @else
            <div class="blog-grid">
                @foreach ($events as $event)
                    <a class="blog-card" href="{{ route('events.show', $event->slug) }}">
                        <div class="blog-card__media" aria-hidden="true">
                            <span class="blog-card__fallback">{{ mb_strtoupper(mb_substr($event->title, 0, 1)) }}</span>
                        </div>
                        <div class="blog-card__body">
                            <div class="blog-card__meta">
                                <span>{{ ucfirst($event->event_type) }}</span>
                                @if ($event->starts_on)
                                    <time>{{ $event->starts_on->format('d.m.Y') }}</time>
                                @elseif ($event->needs_review)
                                    <span>Datum in Prüfung</span>
                                @endif
                            </div>
                            <h2>{{ $event->title }}</h2>
                            <p>
                                @if ($event->city || $event->location_name)
                                    {{ $event->location_name ?: $event->city }}
                                @else
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 120) }}
                                @endif
                            </p>
                            <span class="blog-card__more">Details</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
