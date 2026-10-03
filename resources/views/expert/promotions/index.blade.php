@extends('layouts.expert')

@section('title', 'Promotionen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Promotionen</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'active' => 'Aktiv',
        'expired' => 'Abgelaufen',
        'cancelled' => 'Storniert',
    ];
    $statusPill = [
        'pending' => 'warning',
        'active' => 'success',
        'expired' => 'muted',
        'cancelled' => 'danger',
    ];
@endphp

<div class="xp-page">
    <header class="xp-promo-hero">
        <div>
            <p class="xp-hero__kicker">Sichtbarkeit</p>
            <h1>Kantonale Promotionen</h1>
            <p>Sichern Sie sich Top-Platzierungen (1–3) in einzelnen Kantonen. Monatlich buchbar, klar begrenzt, hohe Sichtbarkeit im Verzeichnis.</p>
        </div>
        <div class="xp-promo-price">
            <span>Monatspreis</span>
            <strong>{{ $monthlyPrice }} <small>CHF</small></strong>
            <em>pro Position · pro Kanton</em>
        </div>
    </header>

    <div class="xp-stat-grid xp-stat-grid--4">
        <div class="xp-stat xp-stat--static">
            <span>Freie Slots</span>
            <strong>{{ $freeSlots }}</strong>
            <em>aktuell verfügbar</em>
        </div>
        <div class="xp-stat xp-stat--static">
            <span>Belegt</span>
            <strong>{{ $takenSlots }}</strong>
            <em>pending / aktiv</em>
        </div>
        <div class="xp-stat xp-stat--static">
            <span>Ihre Aktiven</span>
            <strong>{{ $activeCount }}</strong>
            <em>laufende Promotionen</em>
        </div>
        <div class="xp-stat xp-stat--static">
            <span>Ausstehend</span>
            <strong>{{ $pendingCount }}</strong>
            <em>warten auf Freigabe</em>
        </div>
    </div>

    <section class="xp-panel" style="margin-bottom:1.1rem;">
        <div class="xp-panel__head">
            <div>
                <h2>Ihre Promotionen</h2>
                <p>Anfragen, aktive Platzierungen und Laufzeiten.</p>
            </div>
        </div>
        @if ($promotions->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Promotionen</strong>
                <p>Wählen Sie unten einen freien Slot in einem Kanton.</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Kanton</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Laufzeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promotions as $promotion)
                            <tr>
                                <td><strong>{{ $promotion->canton->name_de ?? '—' }}</strong></td>
                                <td>
                                    <span class="xp-pos xp-pos--{{ $promotion->position_number }}">Platz {{ $promotion->position_number }}</span>
                                </td>
                                <td><span class="pill pill--{{ $statusPill[$promotion->status] ?? 'muted' }}">{{ $statusLabel[$promotion->status] ?? $promotion->status }}</span></td>
                                <td>
                                    @if ($promotion->starts_at)
                                        {{ $promotion->starts_at->format('d.m.Y') }} – {{ $promotion->ends_at?->format('d.m.Y') ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($waitlist->isNotEmpty())
            <div class="xp-waitlist">
                <h3>Warteliste</h3>
                <ul>
                    @foreach ($waitlist as $entry)
                        <li>
                            <strong>{{ $entry->canton->name_de ?? '—' }}</strong>
                            — Position {{ $entry->position_number ?? 'beliebig' }}
                            <span>Reihenfolge {{ $entry->queue_order }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <section class="xp-panel">
        <div class="xp-panel__head xp-panel__head--wrap">
            <div>
                <h2>Verfügbare Positionen</h2>
                <p>Drei Top-Plätze pro Kanton. Belegte Slots können auf die Warteliste gesetzt werden.</p>
            </div>
            <div class="xp-filter">
                <label for="cantonFilter" class="sr-only">Kanton filtern</label>
                <input class="form-control" type="search" id="cantonFilter" placeholder="Kanton suchen…">
            </div>
        </div>

        <div class="xp-canton-grid" id="cantonGrid">
            @foreach ($cantons as $canton)
                @php
                    $freeHere = 0;
                    for ($p = 1; $p <= 3; $p++) {
                        if (!$occupied->has($canton->id.'-'.$p)) $freeHere++;
                    }
                @endphp
                <article class="xp-canton-card" data-name="{{ strtolower($canton->name_de.' '.$canton->code) }}">
                    <header>
                        <div>
                            <strong>{{ $canton->name_de }}</strong>
                            <span>{{ $canton->code }}</span>
                        </div>
                        <em>{{ $freeHere }}/3 frei</em>
                    </header>
                    <div class="xp-slots">
                        @for ($pos = 1; $pos <= 3; $pos++)
                            @php $key = $canton->id.'-'.$pos; $taken = $occupied->has($key); @endphp
                            <div class="xp-slot {{ $taken ? 'is-taken' : 'is-free' }}">
                                <div class="xp-slot__top">
                                    <span class="xp-pos xp-pos--{{ $pos }}">{{ $pos }}</span>
                                    <strong>{{ $taken ? 'Belegt' : 'Frei' }}</strong>
                                </div>
                                @if (!$taken && $expert)
                                    <form method="post" action="{{ route('expert.promotions.request') }}">
                                        @csrf
                                        <input type="hidden" name="canton_id" value="{{ $canton->id }}">
                                        <input type="hidden" name="position_number" value="{{ $pos }}">
                                        <button type="submit" class="btn btn--small">Anfragen</button>
                                    </form>
                                @elseif ($taken && $expert)
                                    <form method="post" action="{{ route('expert.promotions.waitlist') }}">
                                        @csrf
                                        <input type="hidden" name="canton_id" value="{{ $canton->id }}">
                                        <input type="hidden" name="position_number" value="{{ $pos }}">
                                        <button type="submit" class="btn btn--ghost btn--small">Warteliste</button>
                                    </form>
                                @endif
                            </div>
                        @endfor
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>

<script>
(function () {
    var input = document.getElementById('cantonFilter');
    var cards = document.querySelectorAll('.xp-canton-card');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        cards.forEach(function (card) {
            var name = card.getAttribute('data-name') || '';
            card.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
        });
    });
})();
</script>
@endsection
