@extends('layouts.public')

@section('title', 'nachfolge-experten.ch — M&A und Unternehmensnachfolge')
@section('meta_description', 'Finden Sie geprüfte M&A-Experten für Kauf und Verkauf von Unternehmen in der Schweiz — diskret, kantonal und über die Karte.')

@section('hero')
<section class="hero" aria-label="Einstieg">
    <div class="hero__media" role="img" aria-label="Schweizer Berglandschaft"></div>
    <div class="hero__grain" aria-hidden="true"></div>
    <div class="hero__inner">
        <p class="hero__eyebrow">Schweiz · M&A · Nachfolge</p>
        <h1 class="hero__brand">nachfolge-experten.ch</h1>
        <p class="hero__lead">Das unabhängige Verzeichnis für M&A- und Nachfolge-Experten in der Schweiz.</p>
        <div class="hero__actions">
            <a class="btn" href="{{ route('directory.index') }}">Experten finden</a>
            <a class="btn btn--secondary" href="{{ route('map.index') }}">Auf der Karte suchen</a>
        </div>
    </div>
</section>
@endsection

@section('content')
<div class="container">
    @include('partials.flash')

    <section class="section section--flush-top reveal-on-scroll">
        <hr class="section__rule" aria-hidden="true">
        <div class="section__head section__head--asymmetric">
            <div>
                <p class="page-kicker">Verzeichnis</p>
                <h2 class="section__title">Ausgewählte Experten</h2>
            </div>
            <p class="section__desc">Geprüfte Berater für Kauf und Verkauf — regional verankert, diskret erreichbar.</p>
        </div>

        @if ($experts->isEmpty())
            <div class="empty-state">Derzeit sind noch keine Experten öffentlich freigeschaltet.</div>
        @else
            <div class="expert-list">
                @foreach ($experts as $expert)
                    @php $office = $expert->primaryOffice(); @endphp
                    <a class="expert-row" href="{{ route('experts.show', $expert->slug) }}">
                        <div class="expert-row__logo">
                            @if ($expert->logo_path)
                                <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                            @else
                                <span class="expert-row__mark">{{ mb_substr($expert->company_name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div>
                            <h3 class="expert-row__name">{{ $expert->company_name }}</h3>
                            <p class="expert-row__meta">
                                @if ($office)
                                    {{ $office->city }}@if($office->canton) · {{ $office->canton->name_de }}@endif
                                @else
                                    Schweiz
                                @endif
                            </p>
                        </div>
                        <div class="expert-row__side">
                            <div class="chip-row">
                                @if ($expert->offers_buy)<span class="badge badge--buy">Kauf</span>@endif
                                @if ($expert->offers_sell)<span class="badge badge--sell">Verkauf</span>@endif
                            </div>
                            <span class="expert-row__arrow" aria-hidden="true">→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
        <p style="margin-top:1.75rem;text-align:right;">
            <a class="section__link" href="{{ route('directory.index') }}">Alle Experten anzeigen →</a>
        </p>
    </section>
</div>

<div class="section-band reveal-on-scroll" data-delay="1">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="page-kicker">Orientierung</p>
                <h2 class="section__title">Nach Kanton entdecken</h2>
            </div>
            <a class="section__link" href="{{ route('cantons.index') }}">Alle Kantone →</a>
        </div>
        <div class="canton-strip" role="list">
            @foreach (\App\Models\Canton::query()->orderBy('code')->limit(12)->get() as $canton)
                <a class="canton-strip__item" href="{{ route('cantons.show', $canton->code) }}" role="listitem">
                    <strong>{{ $canton->code }}</strong>
                    <span>{{ $canton->name_de }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="container">
    <section class="section reveal-on-scroll" data-delay="2">
        <div class="section__head section__head--asymmetric">
            <div>
                <p class="page-kicker">Wissen</p>
                <h2 class="section__title">Fachbeiträge zur Nachfolge</h2>
            </div>
            <a class="section__link" href="{{ route('blog.index') }}">Zum Blog →</a>
        </div>
        @if ($articles->isEmpty())
            <div class="empty-state">Bald erscheinen hier Beiträge zu Bewertung, Vorbereitung und Nachfolgepraxis.</div>
        @else
            <div class="blog-list">
                @foreach ($articles as $article)
                    <a class="blog-item" href="{{ route('blog.show', $article->slug) }}">
                        <p class="blog-item__date">
                            @if ($article->published_at)
                                {{ $article->published_at->format('d.m.Y') }}
                            @else
                                —
                            @endif
                        </p>
                        <div class="blog-item__body">
                            <h3>{{ $article->title }}</h3>
                            @if ($article->excerpt)
                                <p>{{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}</p>
                            @endif
                        </div>
                        <span class="blog-item__mark" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
