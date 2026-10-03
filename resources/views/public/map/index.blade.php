@extends('layouts.public')

@section('title', 'Karte — nachfolge-experten.ch')
@section('meta_description', 'Schweizer M&A-Experten auf der Karte finden — Standorte, Kantone, Kauf und Verkauf.')
@section('flush', true)
@section('body_class', 'is-map-page')

@section('content')
@php
    $queryBase = array_filter([
        'richtung' => $direction ?? null,
        'kanton' => $cantonCode ?: null,
    ]);
    $markerCount = count($markers ?? []);
@endphp

<div class="map-theater">
    <div class="map-theater__stage">
        @include('partials.google-map', [
            'mapId' => 'switzerlandMap',
            'markers' => $markers ?? [],
            'mapsApiKey' => $mapsApiKey ?? null,
            'height' => '100%',
        ])
    </div>

    <div class="map-hud">
        <header class="map-hud__top">
            <div class="map-hud__brand">
                <p class="map-hud__kicker">Schweiz · Live-Karte</p>
                <h1 class="map-hud__title">Experten finden</h1>
                <p class="map-hud__count">
                    <strong>{{ $markerCount }}</strong>
                    Standort{{ $markerCount === 1 ? '' : 'e' }}
                    @if ($cantonCode) · {{ $cantonCode }} @endif
                    @if ($direction === 'kaufen') · Kauf @elseif ($direction === 'verkaufen') · Verkauf @endif
                </p>
            </div>
            <nav class="map-hud__views" aria-label="Ansicht">
                <a href="{{ route('directory.index', array_merge($queryBase, ['ansicht' => 'liste'])) }}">Liste</a>
                <a class="is-active" href="{{ route('map.index', $queryBase) }}">Karte</a>
                <a href="{{ route('cantons.index') }}">Kantone</a>
            </nav>
        </header>

        <form method="get" class="map-hud__filters" action="{{ route('map.index') }}">
            <div class="map-hud__chips" role="group" aria-label="Richtung">
                <label class="map-hud__chip {{ empty($direction) ? 'is-active' : '' }}">
                    <input type="radio" name="richtung" value="" @checked(empty($direction)) onchange="this.form.submit()"> Alle
                </label>
                <label class="map-hud__chip {{ ($direction ?? '') === 'kaufen' ? 'is-active' : '' }}">
                    <input type="radio" name="richtung" value="kaufen" @checked(($direction ?? '') === 'kaufen') onchange="this.form.submit()"> Kauf
                </label>
                <label class="map-hud__chip {{ ($direction ?? '') === 'verkaufen' ? 'is-active' : '' }}">
                    <input type="radio" name="richtung" value="verkaufen" @checked(($direction ?? '') === 'verkaufen') onchange="this.form.submit()"> Verkauf
                </label>
            </div>
            <div class="map-hud__select">
                <select class="form-control" name="kanton" aria-label="Kanton" onchange="this.form.submit()">
                    <option value="">Alle Kantone</option>
                    @foreach (\App\Models\Canton::query()->orderBy('code')->get() as $canton)
                        <option value="{{ $canton->code }}" @selected(($cantonCode ?? '') === $canton->code)>{{ $canton->code }} — {{ $canton->name_de }}</option>
                    @endforeach
                </select>
            </div>
            <div class="map-hud__legend" aria-label="Legende">
                <span><i class="map-legend__dot map-legend__dot--std"></i> Standard</span>
                <span><i class="map-legend__dot map-legend__dot--promo"></i> Empfohlen</span>
            </div>
        </form>

        @if (($offices ?? collect())->isNotEmpty())
            <aside class="map-hud__panel" aria-label="Standorte">
                <div class="map-hud__panel-head">
                    <div>
                        <p class="map-hud__kicker">Treffer</p>
                        <h2>{{ $markerCount }} Standorte</h2>
                    </div>
                    <a href="{{ route('directory.index', array_merge($queryBase, ['ansicht' => 'liste'])) }}">Liste →</a>
                </div>
                <div class="map-hud__panel-list">
                    @foreach ($offices as $office)
                        @continue(!($office->expert))
                        @php $isPromoted = isset($promotedIds) && $promotedIds->has($office->expert_id); @endphp
                        <a class="map-hud__row @if($isPromoted) is-promoted @endif" href="{{ route('experts.show', $office->expert->slug) }}">
                            <span class="map-hud__pin" aria-hidden="true"></span>
                            <span class="map-hud__row-body">
                                <strong>{{ $office->expert->company_name }}</strong>
                                <em>{{ $office->city }}@if ($office->canton) · {{ $office->canton->code }}@endif</em>
                            </span>
                            @if ($isPromoted)<span class="map-hud__badge">Empfohlen</span>@endif
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif
    </div>
</div>
@endsection
