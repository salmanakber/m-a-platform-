@extends('layouts.expert')

@section('title', 'Promotionen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Promotionen</span>
@endsection

@section('content')
@php
    use App\Support\RankingScope;
    use App\Support\ProfessionalGroup;

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
    $tabPrices = $priceMaps[$activeTab] ?? [];
@endphp

<div class="xp-page">
    <header class="xp-promo-hero">
        <div>
            <p class="xp-hero__kicker">Sichtbarkeit</p>
            <h1>Promotionen</h1>
            <p>
                Buchen Sie Top-Platzierungen: Kanton (Top 6), nationale Fachgruppe / Berater Schweiz,
                Top empfohlen oder Fachbeiträge. Preise gelten je Position und Scope.
            </p>
        </div>
        <div class="xp-promo-price">
            <span>Ihre Aufträge</span>
            <strong>{{ number_format((float) $orderTotal, 0, '.', "'") }} <small>CHF</small></strong>
            <em>Summe offener + aktiver Plätze / Mt.</em>
        </div>
    </header>

    <div class="xp-stat-grid xp-stat-grid--4">
        <div class="xp-stat xp-stat--static">
            <span>Freie Kantonsplätze</span>
            <strong>{{ $freeSlots }}</strong>
            <em>verfügbare Positionen</em>
        </div>
        <div class="xp-stat xp-stat--static">
            <span>Belegt</span>
            <strong>{{ $takenSlots }}</strong>
            <em>pending / aktiv (Kanton)</em>
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
                <p>Anfragen, aktive Platzierungen und Laufzeiten — inkl. individueller Positionspreise.</p>
            </div>
        </div>
        @if ($promotions->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Promotionen</strong>
                <p>Wählen Sie unten einen Scope und einen freien Platz.</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Detail</th>
                            <th>Position</th>
                            <th>Preis / Mt.</th>
                            <th>Status</th>
                            <th>Laufzeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promotions as $promotion)
                            @php
                                $pScope = $promotion->scope ?: RankingScope::CANTON_TOP3;
                                $pPrice = $priceMaps[$pScope][$promotion->position_number]
                                    ?? ($priceMaps[RankingScope::CANTON_TOP3][$promotion->position_number] ?? 0);
                            @endphp
                            <tr>
                                <td><strong>{{ $promotion->scopeLabel() }}</strong></td>
                                <td>
                                    @if ($promotion->canton)
                                        {{ $promotion->canton->name_de }}
                                    @elseif ($promotion->articleCategory)
                                        {{ $promotion->articleCategory->name_de }}
                                    @elseif ($promotion->professional_group)
                                        {{ ProfessionalGroup::label($promotion->professional_group) }}
                                    @else
                                        Schweiz
                                    @endif
                                </td>
                                <td>
                                    <span class="xp-pos xp-pos--{{ $promotion->position_number }}">Platz {{ $promotion->position_number }}</span>
                                </td>
                                <td>{{ number_format((float) $pPrice, 0, '.', "'") }} CHF</td>
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

    <nav class="xp-promo-tabs" aria-label="Promotion-Typen">
        @foreach ($tabs as $scope => $tab)
            <a class="xp-promo-tabs__item {{ $activeTab === $scope ? 'is-active' : '' }}"
               href="{{ route('expert.promotions.index', ['scope' => $scope]) }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <section class="xp-panel">
        <div class="xp-panel__head xp-panel__head--wrap">
            <div>
                <h2>{{ $tabs[$activeTab]['label'] ?? 'Positionen' }}</h2>
                <p>{{ $tabs[$activeTab]['lead'] ?? '' }}</p>
            </div>
            @if ($activeTab === RankingScope::CANTON_TOP3)
                <div class="xp-filter">
                    <label for="cantonFilter" class="sr-only">Kanton filtern</label>
                    <input class="form-control" type="search" id="cantonFilter" placeholder="Kanton suchen…">
                </div>
            @endif
        </div>

        @if ($activeTab === RankingScope::CANTON_TOP3)
            <div class="xp-canton-grid" id="cantonGrid">
                @foreach ($cantons as $canton)
                    @php
                        $freeHere = 0;
                        for ($p = 1; $p <= 6; $p++) {
                            $k = implode(':', [RankingScope::CANTON_TOP3, '', (string) $canton->id, '', (string) $p]);
                            if (! $occupied->has($k)) $freeHere++;
                        }
                        $highlight = $selectedCantonCode && strcasecmp((string) $selectedCantonCode, (string) $canton->code) === 0;
                    @endphp
                    <article class="xp-canton-card {{ $highlight ? 'is-highlight' : '' }}" data-name="{{ strtolower($canton->name_de.' '.$canton->code) }}">
                        <header>
                            <div>
                                <strong>{{ $canton->name_de }}</strong>
                                <span>{{ $canton->code }}</span>
                            </div>
                            <em>{{ $freeHere }}/6 frei</em>
                        </header>
                        <div class="xp-slots">
                            @for ($pos = 1; $pos <= 6; $pos++)
                                @php
                                    $key = implode(':', [RankingScope::CANTON_TOP3, '', (string) $canton->id, '', (string) $pos]);
                                    $taken = $occupied->has($key);
                                    $price = $tabPrices[$pos] ?? 0;
                                @endphp
                                <div class="xp-slot {{ $taken ? 'is-taken' : 'is-free' }}">
                                    <div class="xp-slot__top">
                                        <span class="xp-pos xp-pos--{{ $pos }}">{{ $pos }}</span>
                                        <strong>{{ $taken ? 'Belegt' : 'Frei' }}</strong>
                                    </div>
                                    <div class="xp-slot__price">{{ number_format((float) $price, 0, '.', "'") }} CHF/Mt.</div>
                                    @if (! $taken && $expert)
                                        <form method="post" action="{{ route('expert.promotions.request') }}">
                                            @csrf
                                            <input type="hidden" name="scope" value="{{ RankingScope::CANTON_TOP3 }}">
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
        @elseif ($activeTab === RankingScope::ARTICLE_TOP)
            <div class="xp-canton-grid">
                @foreach ($articleCategories as $category)
                    @php
                        $max = RankingScope::maxPosition(RankingScope::ARTICLE_TOP);
                        $freeHere = 0;
                        for ($p = 1; $p <= $max; $p++) {
                            $k = implode(':', [RankingScope::ARTICLE_TOP, '', '', (string) $category->id, (string) $p]);
                            if (! $occupied->has($k)) $freeHere++;
                        }
                    @endphp
                    <article class="xp-canton-card">
                        <header>
                            <div>
                                <strong>{{ $category->name_de }}</strong>
                                <span>Fachbeitrag</span>
                            </div>
                            <em>{{ $freeHere }}/{{ $max }} frei</em>
                        </header>
                        <div class="xp-slots">
                            @for ($pos = 1; $pos <= $max; $pos++)
                                @php
                                    $key = implode(':', [RankingScope::ARTICLE_TOP, '', '', (string) $category->id, (string) $pos]);
                                    $taken = $occupied->has($key);
                                    $price = $tabPrices[$pos] ?? 0;
                                @endphp
                                <div class="xp-slot {{ $taken ? 'is-taken' : 'is-free' }}">
                                    <div class="xp-slot__top">
                                        <span class="xp-pos xp-pos--{{ $pos }}">{{ $pos }}</span>
                                        <strong>{{ $taken ? 'Belegt' : 'Frei' }}</strong>
                                    </div>
                                    <div class="xp-slot__price">{{ number_format((float) $price, 0, '.', "'") }} CHF/Mt.</div>
                                    @if (! $taken && $expert)
                                        <form method="post" action="{{ route('expert.promotions.request') }}">
                                            @csrf
                                            <input type="hidden" name="scope" value="{{ RankingScope::ARTICLE_TOP }}">
                                            <input type="hidden" name="article_category_id" value="{{ $category->id }}">
                                            <input type="hidden" name="position_number" value="{{ $pos }}">
                                            <button type="submit" class="btn btn--small">Anfragen</button>
                                        </form>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            @php
                $max = RankingScope::maxPosition($activeTab);
                $groupKey = $activeTab === RankingScope::GROUP_TOP ? ($expertGroup ?? '') : '';
            @endphp
            <div class="xp-slots xp-slots--national">
                @for ($pos = 1; $pos <= $max; $pos++)
                    @php
                        $key = implode(':', [$activeTab, $groupKey, '', '', (string) $pos]);
                        $taken = $occupied->has($key);
                        $price = $tabPrices[$pos] ?? 0;
                    @endphp
                    <div class="xp-slot {{ $taken ? 'is-taken' : 'is-free' }}">
                        <div class="xp-slot__top">
                            <span class="xp-pos xp-pos--{{ $pos }}">{{ $pos }}</span>
                            <strong>{{ $taken ? 'Belegt' : 'Frei' }}</strong>
                        </div>
                        <div class="xp-slot__price">{{ number_format((float) $price, 0, '.', "'") }} CHF/Mt.</div>
                        @if (! $taken && $expert)
                            <form method="post" action="{{ route('expert.promotions.request') }}">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $activeTab }}">
                                <input type="hidden" name="position_number" value="{{ $pos }}">
                                <button type="submit" class="btn btn--small">Anfragen</button>
                            </form>
                        @elseif ($taken)
                            <em class="xp-slot__taken">Bereits angefragt / aktiv</em>
                        @endif
                    </div>
                @endfor
            </div>
        @endif
    </section>
</div>

<style>
.xp-promo-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin: 0 0 1rem;
}
.xp-promo-tabs__item {
    display: inline-flex;
    align-items: center;
    padding: .55rem .9rem;
    border-radius: 999px;
    border: 1px solid rgba(14,73,113,.18);
    background: #fff;
    color: #0E4971;
    font-size: .86rem;
    font-weight: 600;
    text-decoration: none;
}
.xp-promo-tabs__item.is-active {
    background: #0E4971;
    border-color: #0E4971;
    color: #fff;
}
.xp-slot__price {
    font-size: .78rem;
    color: #5a6b78;
    margin: .15rem 0 .45rem;
}
.xp-slots--national {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .75rem;
}
.xp-canton-card.is-highlight {
    outline: 2px solid #C5A059;
}
.xp-slot__taken {
    font-size: .75rem;
    color: #8a96a0;
}
</style>

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
