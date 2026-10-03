@extends('layouts.public')

@section('title', 'Kantone — nachfolge-experten.ch')
@section('meta_description', 'Schweizer M&A- und Nachfolge-Experten nach Kanton finden — alle 26 Kantone im Überblick.')
@section('flush', true)

@section('content')
@php
    $maxCount = max(1, (int) ($maxCount ?? 1));
@endphp

<div class="discover discover--cantons">
    <section class="discover-hero">
        <div class="discover-hero__glow" aria-hidden="true"></div>
        <div class="discover-hero__grid" aria-hidden="true"></div>
        <div class="container discover-hero__inner">
            <div class="discover-hero__copy">
                <p class="discover-kicker">Schweiz · 26 Kantone</p>
                <h1 class="discover-title">Nachfolge-Experten <em>regional</em> finden</h1>
                <p class="discover-lead">
                    Dichteübersicht des Verzeichnisses — von Zürich bis Tessin.
                    Intensität zeigt, wo aktuell Experten freigeschaltet sind.
                </p>
                <div class="discover-actions">
                    <a class="btn" href="{{ route('directory.index') }}">Zum Verzeichnis</a>
                    <a class="btn btn--ghost" href="{{ route('map.index') }}">Schweiz-Karte öffnen</a>
                </div>
            </div>
            <aside class="discover-stats" aria-label="Kennzahlen">
                <div class="discover-stat">
                    <strong>26</strong>
                    <span>Kantone</span>
                </div>
                <div class="discover-stat">
                    <strong>{{ $withExperts ?? 0 }}</strong>
                    <span>mit Experten</span>
                </div>
                <div class="discover-stat">
                    <strong>{{ $totalExperts ?? 0 }}</strong>
                    <span>Einträge gesamt</span>
                </div>
            </aside>
        </div>
    </section>

    <div class="container discover-body">
        @if(($topCantons ?? collect())->where('experts_count', '>', 0)->isNotEmpty())
            <section class="canton-spotlight reveal-on-scroll">
                <div class="canton-spotlight__head">
                    <p class="discover-kicker">Hotspots</p>
                    <h2>Stärkste Kantone</h2>
                </div>
                <div class="canton-spotlight__grid">
                    @foreach ($topCantons->where('experts_count', '>', 0) as $hot)
                        @php $heat = min(1, $hot->experts_count / $maxCount); @endphp
                        <a class="canton-spotlight__card" href="{{ route('cantons.show', $hot->code) }}" style="--heat: {{ $heat }}">
                            <div class="canton-spotlight__code">{{ $hot->code }}</div>
                            <div class="canton-spotlight__meta">
                                <strong>{{ $hot->name_de }}</strong>
                                <span>{{ $hot->experts_count }} Experte{{ $hot->experts_count === 1 ? '' : 'n' }}</span>
                            </div>
                            <div class="canton-spotlight__bar" aria-hidden="true"><i style="width: {{ round($heat * 100) }}%"></i></div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="canton-board reveal-on-scroll">
            <div class="canton-board__toolbar">
                <div>
                    <p class="discover-kicker">Alle Kantone</p>
                    <h2 class="canton-board__title">Verzeichnis nach Region</h2>
                </div>
                <div class="dir-view-toggle" role="group" aria-label="Ansichten">
                    <a class="is-active" href="{{ route('cantons.index') }}">Raster</a>
                    <a href="{{ route('directory.index') }}">Liste</a>
                    <a href="{{ route('map.index') }}">Karte</a>
                </div>
            </div>

            <div class="canton-mosaic">
                @forelse ($cantons as $canton)
                    @php
                        $count = (int) ($canton->experts_count ?? 0);
                        $heat = $count > 0 ? max(0.18, min(1, $count / $maxCount)) : 0;
                    @endphp
                    <a class="canton-cell @if($count > 0) canton-cell--live @endif"
                       href="{{ route('cantons.show', $canton->code) }}"
                       style="--heat: {{ $heat }}"
                       data-count="{{ $count }}">
                        <span class="canton-cell__code">{{ $canton->code }}</span>
                        <span class="canton-cell__name">{{ $canton->name_de }}</span>
                        <span class="canton-cell__count">
                            @if ($count > 0)
                                <em>{{ $count }}</em> Experte{{ $count === 1 ? '' : 'n' }}
                            @else
                                Noch offen
                            @endif
                        </span>
                        <span class="canton-cell__pulse" aria-hidden="true"></span>
                    </a>
                @empty
                    <div class="empty-state" style="grid-column:1/-1;">Keine Kantone hinterlegt.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
