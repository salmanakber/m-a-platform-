@extends('layouts.public')

@section('title', $event->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($event->description), 155))

@section('content')
@php
    $typeLabels = [
        'workshop' => 'Workshop',
        'presentation' => 'Vortrag',
        'hearing' => 'Hearing',
        'other' => 'Sonstiges',
    ];
@endphp
<article class="ev-show">
    <header class="ev-show__hero">
        <div class="container ev-show__hero-inner">
            <p class="ev-show__crumb">
                <a href="{{ route('events.index') }}">Events</a>
                <span>/</span>
                <span>{{ $typeLabels[$event->event_type] ?? ucfirst($event->event_type) }}</span>
            </p>
            <h1>{{ $event->title }}</h1>
            <div class="ev-show__meta">
                @if ($event->starts_on)
                    <span>{{ $event->starts_on->translatedFormat('d. F Y') }}</span>
                    @if ($event->starts_at_time)
                        <span>{{ \Illuminate\Support\Str::of($event->starts_at_time)->substr(0, 5) }} Uhr</span>
                    @endif
                @else
                    <span>Datum noch nicht bestätigt</span>
                @endif
                @if ($event->city || $event->location_name)
                    <span>{{ $event->location_name ?: $event->city }}</span>
                @endif
                @if ($event->expert)
                    <span>{{ $event->expert->company_name }}</span>
                @endif
            </div>
            <div class="ev-show__actions">
                <a class="btn btn--small" href="{{ route('map.index', ['events' => 1, 'kanton' => optional($event->canton)->code]) }}">Auf der Karte</a>
                @if ($event->source_url)
                    <a class="btn btn--ghost btn--small" href="{{ $event->source_url }}" target="_blank" rel="noopener">Originalquelle</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container ev-show__layout">
        <div class="ev-show__main">
            @if ($event->needs_review)
                <div class="xp-notice xp-notice--warn" style="margin-bottom:1.25rem;">
                    <strong>Hinweis</strong>
                    <p>Angaben zu Datum oder Ort konnten nicht vollständig verifiziert werden.</p>
                </div>
            @endif

            <div class="article-prose">{!! $event->description ?: '<p>Details folgen.</p>' !!}</div>
        </div>

        <aside class="ev-show__aside">
            <section class="ev-fact">
                <h2>Details</h2>
                <dl>
                    <div>
                        <dt>Typ</dt>
                        <dd>{{ $typeLabels[$event->event_type] ?? ucfirst($event->event_type) }}</dd>
                    </div>
                    @if ($event->starts_on)
                        <div>
                            <dt>Datum</dt>
                            <dd>
                                {{ $event->starts_on->format('d.m.Y') }}
                                @if ($event->starts_at_time)
                                    · {{ \Illuminate\Support\Str::of($event->starts_at_time)->substr(0, 5) }} Uhr
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($event->location_name || $event->address_line || $event->city)
                        <div>
                            <dt>Ort</dt>
                            <dd>
                                @if ($event->location_name){{ $event->location_name }}<br>@endif
                                @if ($event->address_line){{ $event->address_line }}<br>@endif
                                {{ trim(($event->postal_code ?? '').' '.($event->city ?? '')) }}
                                @if ($event->canton) · {{ $event->canton->name_de }}@endif
                            </dd>
                        </div>
                    @endif
                    @if ($event->source_url)
                        <div>
                            <dt>Quelle</dt>
                            <dd><a href="{{ $event->source_url }}" target="_blank" rel="noopener">Zur Website</a></dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($event->expert && $event->expert->is_public)
                <section class="ev-fact ev-fact--host">
                    <p class="ev-fact__label">Veranstalter</p>
                    <strong>{{ $event->expert->company_name }}</strong>
                    <a class="btn btn--small" href="{{ route('experts.show', $event->expert->slug) }}">Profil ansehen</a>
                </section>
            @endif
        </aside>
    </div>
</article>
@endsection
