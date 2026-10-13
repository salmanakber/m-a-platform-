@extends('layouts.public')

@section('title', $event->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($event->description), 155))

@section('content')
<article class="blog-article">
    <header class="blog-article__hero">
        <div class="container blog-article__hero-inner">
            <p class="blog-article__crumb">
                <a href="{{ route('events.index') }}">Events</a>
                <span>/</span>
                <span>{{ ucfirst($event->event_type) }}</span>
            </p>
            <h1>{{ $event->title }}</h1>
            <div class="blog-article__meta">
                @if ($event->starts_on)
                    <time>{{ $event->starts_on->format('d. F Y') }}</time>
                    @if ($event->starts_at_time)
                        <span>· {{ \Illuminate\Support\Str::of($event->starts_at_time)->substr(0, 5) }} Uhr</span>
                    @endif
                @elseif ($event->needs_review)
                    <span>Datum noch nicht bestätigt</span>
                @endif
                @if ($event->expert)
                    <span>·</span>
                    <span>{{ $event->expert->company_name }}</span>
                @endif
            </div>
        </div>
    </header>

    <div class="container blog-article__layout">
        <div class="blog-article__content">
            @if ($event->needs_review)
                <p class="xp-notice xp-notice--warn" style="margin-bottom:1.25rem;">
                    <strong>Hinweis</strong>
                    Angaben zu Datum oder Ort konnten nicht vollständig verifiziert werden.
                </p>
            @endif

            <div class="article-prose">{!! $event->description ?: '<p>Details folgen.</p>' !!}</div>

            <table class="entity-table" style="margin-top:1.5rem;">
                <tbody>
                    @if ($event->location_name || $event->address_line || $event->city)
                        <tr>
                            <th>Ort</th>
                            <td>
                                {{ $event->location_name }}
                                @if ($event->address_line)<br>{{ $event->address_line }}@endif
                                @if ($event->postal_code || $event->city)
                                    <br>{{ trim(($event->postal_code ?? '').' '.($event->city ?? '')) }}
                                @endif
                                @if ($event->canton) · {{ $event->canton->name_de }}@endif
                            </td>
                        </tr>
                    @endif
                    @if ($event->source_url)
                        <tr>
                            <th>Quelle</th>
                            <td><a href="{{ $event->source_url }}" target="_blank" rel="noopener">{{ $event->source_url }}</a></td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @if ($event->expert && $event->expert->is_public)
                <aside class="blog-article__expert">
                    <div>
                        <p class="blog-article__expert-label">Veranstalter</p>
                        <strong>{{ $event->expert->company_name }}</strong>
                    </div>
                    <a class="btn btn--small" href="{{ route('experts.show', $event->expert->slug) }}">Profil</a>
                </aside>
            @endif
        </div>
    </div>
</article>
@endsection
