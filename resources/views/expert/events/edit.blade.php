@extends('layouts.expert')

@section('title', 'Event bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.events.index') }}">Events</a>
    <span>/</span>
    <span>Bearbeiten</span>
@endsection

@section('content')
<div class="xp-page xp-article-studio">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Events</p>
            <h1>{{ $event->title }}</h1>
            <p>
                {{ $event->status }}
                @if ($event->is_crawled) · von Website übernommen @endif
                @if ($event->needs_review) · Prüfung empfohlen @endif
            </p>
        </div>
    </header>

    @include('expert.events._ai-panel')
    @include('partials.flash')

    @if ($event->needs_review || $event->status === 'draft')
        <div class="xp-notice xp-notice--warn">
            <strong>Freigabe erforderlich</strong>
            <p>Dieses Event ist noch nicht öffentlich. Prüfen Sie Titel, Datum und Ort — dann veröffentlichen.</p>
        </div>
    @endif

    <form method="post" action="{{ route('expert.events.update', $event) }}" class="xp-panel" id="eventForm">
        @csrf
        @method('PUT')
        @include('expert.events._form', ['event' => $event])
        <div class="xp-article-panel__actions">
            <button type="submit" name="action" value="save" class="btn btn--ghost">Als Entwurf speichern</button>
            <button type="submit" name="action" value="publish" class="btn">Prüfen &amp; veröffentlichen</button>
            <a class="btn btn--ghost" href="{{ route('expert.events.index') }}">Zurück</a>
        </div>
    </form>

    <form method="post" action="{{ route('expert.events.destroy', $event) }}" class="xp-danger-strip" onsubmit="return confirm('Event wirklich löschen?');">
        @csrf
        @method('DELETE')
        <div><strong>Event löschen</strong><p>Entfernt die Veranstaltung dauerhaft.</p></div>
        <button type="submit" class="btn btn--ghost">Löschen</button>
    </form>
</div>
@endsection
