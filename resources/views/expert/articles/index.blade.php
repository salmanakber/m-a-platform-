@extends('layouts.expert')

@section('title', 'Artikel')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Artikel</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Wachstum</p>
            <h1>Artikel</h1>
            <p>Fachbeiträge für Sichtbarkeit — selbst schreiben, mit KI entwerfen oder von Ihrer Website übernehmen.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--small" href="{{ route('expert.articles.create') }}">Neuer Artikel</a>
            <a class="btn btn--ghost btn--small" href="{{ route('expert.crawl.index') }}">Website-Import</a>
        </div>
    </header>

    <section class="xp-panel">
        @if ($articles->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Artikel</strong>
                <p>Erstellen Sie den ersten Beitrag oder importieren Sie Inhalte von Ihrer Website.</p>
                <p style="margin-top:1rem;">
                    <a class="btn btn--small" href="{{ route('expert.articles.create') }}">Artikel schreiben</a>
                </p>
            </div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Status</th>
                            <th>Quelle</th>
                            <th>Datum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td><strong>{{ $article->title }}</strong></td>
                                <td>{{ $article->status }}</td>
                                <td>{{ $article->is_crawled ? 'Website' : 'Manuell / KI' }}</td>
                                <td>{{ $article->published_at?->format('d.m.Y') ?? $article->created_at->format('d.m.Y') }}</td>
                                <td><a class="btn btn--ghost btn--small" href="{{ route('expert.articles.edit', $article) }}">Bearbeiten</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $articles->links() }}</div>
        @endif
    </section>
</div>
@endsection
