@extends('layouts.public')

@section('title', 'Wissen & Fachbeiträge')
@section('meta_description', 'Fachbeiträge zu Unternehmensnachfolge, Bewertung, Recht und Praxis aus dem Schweizer M&A-Markt.')
@section('body_class', 'page-blog')

@section('content')
<div class="blog-page">
    <header class="blog-hero">
        <div class="container blog-hero__inner">
            <p class="blog-hero__kicker">Nachfolge-Wissen</p>
            <h1>Fachbeiträge</h1>
            <p class="blog-hero__lead">Praxisnahe Artikel zu Bewertung, Vorbereitung, Recht &amp; Steuern sowie Erfahrungsberichten aus dem Schweizer Nachfolgemarkt.</p>
        </div>
    </header>

    <div class="container blog-page__body reveal-on-scroll">
        @if ($articles->isEmpty())
            <div class="blog-empty">
                <strong>Noch keine Beiträge</strong>
                <p>Experten können Fachartikel aus ihrem Bereich veröffentlichen — bald erscheint hier neues Wissen.</p>
            </div>
        @else
            <div class="blog-grid">
                @foreach ($articles as $article)
                    <a class="blog-card" href="{{ route('blog.show', $article->slug) }}">
                        <div class="blog-card__media" aria-hidden="true">
                            @if ($article->cover_image)
                                <img src="{{ asset('storage/'.$article->cover_image) }}" alt="">
                            @else
                                <span class="blog-card__fallback">{{ mb_strtoupper(mb_substr($article->title, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="blog-card__body">
                            <div class="blog-card__meta">
                                @if ($article->category)
                                    <span>{{ $article->category->name_de }}</span>
                                @endif
                                @if ($article->published_at)
                                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d.m.Y') }}</time>
                                @endif
                            </div>
                            <h2>{{ $article->title }}</h2>
                            @if ($article->excerpt)
                                <p>{{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}</p>
                            @endif
                            <span class="blog-card__more">Weiterlesen</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $articles->links() }}</div>
        @endif
    </div>
</div>
@endsection
