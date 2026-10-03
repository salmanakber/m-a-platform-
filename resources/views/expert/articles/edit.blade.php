@extends('layouts.expert')

@section('title', 'Artikel bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.articles.index') }}">Artikel</a>
    <span>/</span>
    <span>Bearbeiten</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'published' => 'Veröffentlicht',
        'draft' => 'Entwurf',
        'archived' => 'Archiviert',
    ];
@endphp
<div class="xp-page xp-article-studio">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Inhalte</p>
            <h1>{{ $article->title }}</h1>
            <p>
                {{ $statusLabel[$article->status] ?? $article->status }}
                @if ($article->is_crawled)
                    · von Ihrer Website übernommen
                @endif
            </p>
        </div>
        <div class="xp-hero__actions">
            @if ($article->status === 'published' && $article->slug)
                <a class="btn btn--ghost btn--small" href="{{ route('blog.show', $article->slug) }}" target="_blank" rel="noopener">Im Blog ansehen</a>
            @endif
        </div>
    </header>

    @include('expert.articles._ai-panel')

    <form method="post" action="{{ route('expert.articles.update', $article) }}" enctype="multipart/form-data" class="xp-panel xp-article-panel" id="articleForm">
        @csrf
        @method('PUT')
        <div class="xp-panel__head">
            <div>
                <h2>Beitrag bearbeiten</h2>
                <p>Änderungen werden nach dem Speichern im Blog sichtbar.</p>
            </div>
        </div>
        @include('expert.articles._form', ['article' => $article])
        <div class="xp-article-panel__actions">
            <button type="submit" class="btn">Speichern</button>
            <a class="btn btn--ghost" href="{{ route('expert.articles.index') }}">Zurück zur Liste</a>
        </div>
    </form>

    <form method="post" action="{{ route('expert.articles.destroy', $article) }}" class="xp-danger-strip" onsubmit="return confirm('Artikel wirklich löschen?');">
        @csrf
        @method('DELETE')
        <div>
            <strong>Artikel löschen</strong>
            <p>Entfernt den Beitrag dauerhaft aus dem Blog.</p>
        </div>
        <button type="submit" class="btn btn--ghost">Löschen</button>
    </form>
</div>
@endsection
