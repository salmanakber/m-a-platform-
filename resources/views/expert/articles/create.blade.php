@extends('layouts.expert')

@section('title', 'Artikel erstellen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.articles.index') }}">Artikel</a>
    <span>/</span>
    <span>Neu</span>
@endsection

@section('content')
<div class="xp-page xp-article-studio">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Inhalte</p>
            <h1>Neuer Artikel</h1>
            <p>Schreiben Sie selbst oder lassen Sie einen Entwurf erzeugen — im Editor verfeinern und veröffentlichen.</p>
        </div>
    </header>

    @include('expert.articles._ai-panel')

    <form method="post" action="{{ route('expert.articles.store') }}" enctype="multipart/form-data" class="xp-panel xp-article-panel" id="articleForm">
        @csrf
        <div class="xp-panel__head">
            <div>
                <h2>Beitrag</h2>
                <p>Titel, Auszug und Inhalt — nach dem Speichern öffentlich im Blog.</p>
            </div>
        </div>
        @include('expert.articles._form', ['article' => null])
        <div class="xp-article-panel__actions">
            <button type="submit" class="btn">Veröffentlichen</button>
            <a class="btn btn--ghost" href="{{ route('expert.articles.index') }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
