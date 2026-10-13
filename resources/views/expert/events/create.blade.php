@extends('layouts.expert')

@section('title', 'Event erstellen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.events.index') }}">Events</a>
    <span>/</span>
    <span>Neu</span>
@endsection

@section('content')
<div class="xp-page xp-article-studio">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Events</p>
            <h1>Neues Event</h1>
            <p>Manuell erfassen oder optional einen KI-Entwurf erzeugen — immer prüfen vor dem Speichern.</p>
        </div>
    </header>

    @include('expert.events._ai-panel')
    @include('partials.flash')

    <form method="post" action="{{ route('expert.events.store') }}" class="xp-panel" id="eventForm">
        @csrf
        @include('expert.events._form', ['event' => null])
        <div class="xp-article-panel__actions">
            <button type="submit" class="btn">Veröffentlichen</button>
            <a class="btn btn--ghost" href="{{ route('expert.events.index') }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
