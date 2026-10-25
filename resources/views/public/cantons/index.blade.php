@extends('layouts.public')

@section('title', 'Kantone — nachfolge-experten.ch')
@section('meta_description', 'Schweizer M&A- und Nachfolge-Experten nach Kanton finden — alle 26 Kantone im Überblick.')
@section('flush', true)

@section('content')
<div class="nh-cantons-page">
    <section class="nh-cantons-hero">
        <div class="nh-cantons-hero__grid" aria-hidden="true"></div>
        <div class="nh-cantons-hero__inner">
            <p class="nh-kicker">Kantons-Auswahl</p>
            <h1>Nach Kanton entdecken</h1>
            <p>Alle 26 Kantone, ein Klick: Wählen Sie einen Kanton und sehen Sie sofort die Top-Experten vor Ort.</p>
            <div class="nh-cantons-hero__actions">
                <a class="nh-btn nh-btn--gold" href="{{ route('map.index') }}">Schweiz-Karte öffnen ↗</a>
                <a class="nh-btn nh-btn--ghost" href="{{ route('directory.index') }}">Zum Verzeichnis</a>
            </div>
            <div class="nh-cantons-stats">
                <div><b>26</b><span>Kantone</span></div>
                <div><b>{{ $withExperts ?? 0 }}</b><span>mit Experten</span></div>
                <div><b>{{ $totalExperts ?? 0 }}</b><span>Einträge gesamt</span></div>
            </div>
        </div>
    </section>

    <section class="nh-cantons-board">
        <div class="nh-cantons-board__inner">
            <div class="nh-section__head nh-section__head--light" style="margin-bottom:1.5rem;">
                <div>
                    <p class="nh-kicker">Übersicht</p>
                    <h2 style="color:#fff;margin:0.25rem 0;">Alle Kantone</h2>
                </div>
                <p class="nh-cantons-note">Kantone mit Experten sind hervorgehoben · Auswahl öffnet die Kantonsseite</p>
            </div>

            <div class="nh-canton-grid">
                @forelse ($cantons as $canton)
                    @php $count = (int) ($canton->experts_count ?? 0); @endphp
                    <a class="nh-canton-tile {{ $count > 0 ? 'is-live' : 'is-empty' }}"
                       href="{{ $count > 0 ? route('cantons.show', $canton->code) : route('home', ['kanton' => $canton->code]).'#kantone-top' }}">
                        <b>{{ $canton->code }}</b>
                        <span>{{ $canton->name_de }}</span>
                        <em>{{ $count > 0 ? $count : '—' }}</em>
                    </a>
                @empty
                    <div class="nh-empty nh-empty--dark" style="grid-column:1/-1;">Keine Kantone hinterlegt.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
