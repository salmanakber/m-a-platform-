@extends('layouts.public')

@section('title', ($activeCategory->name_de ?? 'Wissen & Fachbeiträge'))
@section('meta_description', $activeCategory->description_de ?? 'Fachbeiträge zu Unternehmensnachfolge, Bewertung, Recht und Praxis aus dem Schweizer M&A-Markt.')
@section('flush', true)
@section('body_class', 'page-blog')

@section('content')
<div class="nh-blog-page">
    <header class="nh-page-hero nh-page-hero--dark">
        <div class="nh-page-hero__inner">
            <p class="nh-kicker">Wissen · Fachbeiträge</p>
            <h1>{{ $activeCategory->name_de ?? 'Fachbeiträge zur Nachfolge' }}</h1>
            <p>{{ $activeCategory->description_de ?? 'Konkretes Wissen zu Bewertung, Vorbereitung und Nachfolgepraxis — von den Experten des Verzeichnisses.' }}</p>
        </div>
    </header>

    <div class="nh-blog-page__body">
        @if (($categories ?? collect())->isNotEmpty())
            <nav class="nh-blog-cats" aria-label="Fachbereiche">
                <a class="{{ empty($activeCategory) ? 'is-on' : '' }}" href="{{ route('blog.index') }}">Alle</a>
                @foreach ($categories as $cat)
                    <a class="{{ optional($activeCategory)->id === $cat->id ? 'is-on' : '' }}"
                       href="{{ route('blog.index', ['gruppe' => $cat->slug]) }}">{{ $cat->name_de }}</a>
                @endforeach
            </nav>
        @endif

        @if (($topCards ?? collect())->isNotEmpty())
            <section class="nh-blog-top" aria-label="Top 3 in dieser Gruppe">
                <header>
                    <h2>Top 3 in {{ $activeCategory->name_de }}</h2>
                    <p>Kuratierte Fachbeiträge in diesem Fachbereich.</p>
                </header>
                <div class="nh-blog-grid nh-blog-grid--top">
                    @foreach ($topCards as $index => $article)
                        <a class="nh-blog-card" href="{{ route('blog.show', $article->slug) }}">
                            <div class="nh-blog-card__top">
                                <span class="nh-blog-card__tag">Top {{ $index + 1 }}</span>
                            </div>
                            <h2>{{ $article->title }}</h2>
                            @if ($article->excerpt)
                                <p>{{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}</p>
                            @endif
                            <div class="nh-blog-card__foot">
                                @if ($article->published_at)
                                    <span>{{ $article->published_at->translatedFormat('d. F Y') }}</span>
                                @endif
                                <strong>Lesen ↗</strong>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($articles->isEmpty())
            <div class="nh-empty">
                <strong>Noch keine Beiträge</strong>
                <p>Experten können Fachartikel aus ihrem Bereich veröffentlichen — bald erscheint hier neues Wissen.</p>
            </div>
        @else
            <div class="nh-blog-grid">
                @foreach ($articles as $index => $article)
                    <a class="nh-blog-card" href="{{ route('blog.show', $article->slug) }}">
                        <div class="nh-blog-card__top">
                            @if ($article->category)
                                <span class="nh-blog-card__tag">{{ $article->category->name_de }}</span>
                            @else
                                <span class="nh-blog-card__tag">Fachbeitrag</span>
                            @endif
                            <em>{{ str_pad((string) ($articles->firstItem() + $index), 2, '0', STR_PAD_LEFT) }}</em>
                        </div>
                        <h2>{{ $article->title }}</h2>
                        @if ($article->excerpt)
                            <p>{{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}</p>
                        @endif
                        <div class="nh-blog-card__foot">
                            @if ($article->published_at)
                                <span>{{ $article->published_at->translatedFormat('d. F Y') }}</span>
                            @endif
                            <strong>Lesen ↗</strong>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $articles->links() }}</div>
        @endif
    </div>
</div>
@endsection
