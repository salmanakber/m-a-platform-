@extends('layouts.public')

@section('title', 'Experten in '.$canton->name_de.' — nachfolge-experten.ch')
@section('meta_description', 'M&A- und Nachfolge-Experten im Kanton '.$canton->name_de.' finden.')
@section('flush', true)

@section('content')
<div class="discover discover--canton-show">
    <section class="discover-hero discover-hero--compact">
        <div class="discover-hero__glow" aria-hidden="true"></div>
        <div class="container discover-hero__inner">
            <div class="discover-hero__copy">
                <p class="discover-kicker">
                    <a href="{{ route('cantons.index') }}">← Alle Kantone</a>
                    · {{ $canton->code }}
                </p>
                <h1 class="discover-title">{{ $canton->name_de }}</h1>
                <p class="discover-lead">
                    {{ $experts->count() }} freigeschaltete
                    Experte{{ $experts->count() === 1 ? '' : 'n' }} mit Standort in diesem Kanton.
                </p>
                <div class="discover-actions">
                    <a class="btn" href="{{ route('directory.index', ['kanton' => $canton->code]) }}">Im Verzeichnis</a>
                    <a class="btn btn--ghost" href="{{ route('map.index', ['kanton' => $canton->code]) }}">Auf der Karte</a>
                </div>
            </div>
            <aside class="discover-stats discover-stats--single" aria-label="Kanton">
                <div class="discover-stat">
                    <strong>{{ $canton->code }}</strong>
                    <span>{{ $experts->count() }} Profile</span>
                </div>
            </aside>
        </div>
    </section>

    <div class="container discover-body">
        @if ($experts->isEmpty())
            <div class="empty-state empty-state--panel">
                <p><strong>Noch keine Experten in {{ $canton->name_de }}.</strong></p>
                <p>Schauen Sie im Gesamtverzeichnis oder auf der Schweiz-Karte nach Alternativen.</p>
                <a class="btn btn--small" href="{{ route('directory.index') }}">Verzeichnis öffnen</a>
            </div>
        @else
            <div class="dir-list dir-list--dossier reveal-on-scroll">
                @foreach ($experts as $expert)
                    @php $office = $expert->primaryOffice(); @endphp
                    <article class="dir-card dir-card--dossier">
                        <div class="dir-card__rail" aria-hidden="true"></div>
                        <div class="dir-card__logo">
                            @if ($expert->logo_path)
                                <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                            @else
                                <span>{{ mb_strtoupper(mb_substr($expert->company_name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="dir-card__body">
                            <h2 class="dir-card__name">
                                <a href="{{ route('experts.show', $expert->slug) }}">{{ $expert->company_name }}</a>
                            </h2>
                            <p class="dir-card__loc">
                                @if ($office)
                                    <span class="dir-card__canton">{{ $canton->code }}</span>
                                    {{ $office->city }}
                                    <br><span class="dir-card__addr">{{ $office->address_line }}, {{ $office->postal_code }} {{ $office->city }}</span>
                                @endif
                            </p>
                            <div class="dir-card__tags">
                                @if ($expert->offers_buy)<span class="badge badge--buy">Kauf</span>@endif
                                @if ($expert->offers_sell)<span class="badge badge--sell">Verkauf</span>@endif
                            </div>
                        </div>
                        <div class="dir-card__cta">
                            <a class="btn btn--small" href="{{ route('experts.show', $expert->slug) }}">Profil &amp; Anfrage</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
