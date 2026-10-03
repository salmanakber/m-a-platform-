@extends('layouts.public')

@section('title', $article->seo_title ?: $article->title)
@section('meta_description', $article->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?: $article->body), 155))
@section('body_class', 'page-blog-show')

@section('content')
<article class="blog-article">
    <header class="blog-article__hero">
        <div class="container blog-article__hero-inner">
            <p class="blog-article__crumb">
                <a href="{{ route('blog.index') }}">Wissen</a>
                @if ($article->category)
                    <span>/</span>
                    <span>{{ $article->category->name_de }}</span>
                @endif
            </p>
            <h1>{{ $article->title }}</h1>
            <div class="blog-article__meta">
                @if ($article->published_at)
                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d. F Y') }}</time>
                @endif
                @if ($article->expert)
                    <span>·</span>
                    <span>{{ $article->expert->company_name }}</span>
                @endif
            </div>
            @if ($article->excerpt)
                <p class="blog-article__deck">{{ $article->excerpt }}</p>
            @endif
        </div>
    </header>

    @if ($article->cover_image)
        <div class="blog-article__cover">
            <div class="container">
                <img src="{{ asset('storage/'.$article->cover_image) }}" alt="">
            </div>
        </div>
    @endif

    <div class="container blog-article__layout">
        <div class="blog-article__content reveal-on-scroll">
            <div class="article-prose">{!! $article->body ?: '<p>Inhalt folgt.</p>' !!}</div>

            @if (($article->images ?? collect())->isNotEmpty())
                <div class="blog-article__gallery">
                    @foreach ($article->images as $image)
                        <figure>
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $article->title }}">
                            @if ($image->alt_text)
                                <figcaption>{{ $image->alt_text }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif

            @if ($article->source_attribution || $article->source_url)
                <p class="article-source">
                    Quelle:
                    @if ($article->source_url)
                        <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer">{{ $article->source_attribution ?: $article->source_url }}</a>
                    @else
                        {{ $article->source_attribution }}
                    @endif
                </p>
            @endif

            @if ($article->expert && $article->expert->is_public && $article->expert->status === 'approved')
                <aside class="blog-article__expert">
                    <div>
                        <p class="blog-article__expert-label">Beitrag von</p>
                        <strong>{{ $article->expert->company_name }}</strong>
                    </div>
                    <a class="btn btn--small" href="{{ route('experts.show', $article->expert->slug) }}">Profil ansehen</a>
                </aside>
            @endif
        </div>
    </div>
</article>
@endsection
