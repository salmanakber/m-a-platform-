@extends('layouts.public')

@section('title', 'Expertenverzeichnis — nachfolge-experten.ch')
@section('meta_description', 'Schweizer Verzeichnis für M&A- und Nachfolge-Experten — Liste und Karte gleichzeitig.')
@section('flush', true)
@section('body_class', 'is-directory-page')

@section('content')
@php
    $queryBase = array_filter([
        'richtung' => $direction ?? null,
        'kanton' => $cantonCode ?: null,
        'q' => $q ?: null,
    ]);
@endphp

<div class="dir-explorer" id="dirExplorer" data-map-id="directorySplitMap">
    <div class="dir-explorer__chrome">
        <form method="get" class="dx-bar" action="{{ route('directory.index') }}">
            <div class="dx-bar__identity">
                <span class="dx-bar__mark" aria-hidden="true"></span>
                <div>
                    <h1 class="dx-bar__title">Expertenverzeichnis</h1>
                    <p class="dx-bar__sub">
                        <b>{{ $experts->total() }}</b> passende Profile
                        @if ($totalPublic !== $experts->total())
                            <span>· {{ $totalPublic }} schweizweit</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="dx-bar__search">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input type="search" name="q" id="q" value="{{ $q }}" placeholder="Firma, Ort oder PLZ suchen…" autocomplete="off">
            </div>

            <div class="dx-bar__seg" role="group" aria-label="Richtung">
                <label class="{{ empty($direction) ? 'is-on' : '' }}">
                    <input type="radio" name="richtung" value="" @checked(empty($direction)) onchange="this.form.submit()"> Alle
                </label>
                <label class="{{ ($direction ?? '') === 'kaufen' ? 'is-on' : '' }}">
                    <input type="radio" name="richtung" value="kaufen" @checked(($direction ?? '') === 'kaufen') onchange="this.form.submit()"> Kauf
                </label>
                <label class="{{ ($direction ?? '') === 'verkaufen' ? 'is-on' : '' }}">
                    <input type="radio" name="richtung" value="verkaufen" @checked(($direction ?? '') === 'verkaufen') onchange="this.form.submit()"> Verkauf
                </label>
            </div>

            <div class="dx-bar__select">
                <select name="kanton" id="kanton" aria-label="Kanton" onchange="this.form.submit()">
                    <option value="">Alle Kantone</option>
                    @foreach ($cantons as $canton)
                        <option value="{{ $canton->code }}" @selected(($cantonCode ?? '') === $canton->code)>
                            {{ $canton->code }} — {{ $canton->name_de }}@if($canton->experts_count) ({{ $canton->experts_count }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dx-bar__end">
                <button type="submit" class="dx-bar__go">Suchen</button>
                @if ($q || $direction || $cantonCode)
                    <a class="dx-bar__clear" href="{{ route('directory.index') }}" title="Filter zurücksetzen">×</a>
                @endif
                <a class="dx-bar__ghost" href="{{ route('map.index', $queryBase) }}">Vollkarte</a>
            </div>
        </form>
    </div>

    <div class="dir-explorer__split">
        <section class="dir-explorer__list" aria-label="Ergebnisliste">
            <div class="dx-rail-head">
                <div>
                    <p class="dx-rail-head__label">Ergebnisse</p>
                    <p class="dx-rail-head__range">
                        @if ($experts->total() > 0)
                            {{ $experts->firstItem() }}–{{ $experts->lastItem() }}
                            <span>von {{ $experts->total() }}</span>
                        @else
                            Keine Treffer
                        @endif
                    </p>
                </div>
                <p class="dx-rail-head__hint">Hover synchronisiert die Karte</p>
            </div>

            @if ($experts->isEmpty())
                <div class="empty-state empty-state--panel">
                    <p><strong>Keine Experten für diese Auswahl.</strong></p>
                    <p>Passen Sie Suche, Kanton oder Kauf/Verkauf an.</p>
                </div>
            @else
                <div class="dir-explorer__cards">
                    @foreach ($experts as $expert)
                        @php
                            $office = $expert->primaryOffice();
                            $isPromoted = isset($promotedExpertIds) && $promotedExpertIds->has($expert->id);
                            $markerOffice = $expert->offices->first(function ($o) {
                                return ($o->latitude && $o->longitude) || ($o->canton && $o->canton->latitude && $o->canton->longitude);
                            }) ?? $office;
                            $idx = ($experts->firstItem() ?? 1) + $loop->index;
                        @endphp
                        <article
                            class="dx-item @if($isPromoted) is-promoted @endif"
                            data-expert-id="{{ $expert->id }}"
                            data-office-id="{{ $markerOffice->id ?? '' }}"
                            tabindex="0"
                        >
                            <div class="dx-item__index">{{ str_pad((string) $idx, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="dx-item__media">
                                @if ($expert->logo_path)
                                    <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                                @else
                                    <span>{{ mb_strtoupper(mb_substr($expert->company_name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="dx-item__main">
                                <div class="dx-item__headline">
                                    <h2>
                                        <a href="{{ route('experts.show', $expert->slug) }}">{{ $expert->company_name }}</a>
                                    </h2>
                                    @if ($isPromoted)
                                        <em>Empfohlen</em>
                                    @endif
                                </div>

                                <p class="dx-item__place">
                                    @if ($office)
                                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M12 2c-3.9 0-7 3-7 7 0 5.2 7 13 7 13s7-7.8 7-13c0-4-3.1-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>
                                        @if ($office->canton)<b>{{ $office->canton->code }}</b>@endif
                                        {{ $office->city }}
                                        <span>{{ $office->address_line }}</span>
                                    @else
                                        Schweiz
                                    @endif
                                </p>

                                <div class="dx-item__meta">
                                    @if ($expert->offers_buy)<span>Kauf</span>@endif
                                    @if ($expert->offers_sell)<span>Verkauf</span>@endif
                                    @if ($expert->offices->count() > 1)
                                        <span class="is-soft">{{ $expert->offices->count() }} Standorte</span>
                                    @endif
                                </div>

                                <div class="dx-item__actions">
                                    <button type="button" class="dx-item__mapbtn" data-focus-map>Auf Karte zeigen</button>
                                    <a class="dx-item__profile" href="{{ route('experts.show', $expert->slug) }}">
                                        Profil
                                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" d="M5 12h14M13 6l6 6-6 6"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="dir-explorer__pager">{{ $experts->withQueryString()->links() }}</div>
            @endif
        </section>

        <aside class="dir-explorer__map" aria-label="Karte">
            <div class="dir-explorer__map-frame">
                @include('partials.google-map', [
                    'mapId' => 'directorySplitMap',
                    'markers' => $mapMarkers ?? [],
                    'mapsApiKey' => $mapsApiKey ?? null,
                    'height' => '100%',
                    'interactive' => true,
                ])
            </div>
            <div class="dir-explorer__map-legend">
                <span><i class="map-legend__dot map-legend__dot--std"></i> Standard</span>
                <span><i class="map-legend__dot map-legend__dot--promo"></i> Empfohlen</span>
                <span>{{ count($mapMarkers ?? []) }} Standorte</span>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('dirExplorer');
    if (!root) return;
    var mapId = root.getAttribute('data-map-id');

    function api() {
        return (window.__NE_MAP_API || {})[mapId] || null;
    }

    function setActive(expertId, scroll) {
        root.querySelectorAll('.dx-item.is-active, .dx-card.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });
        if (!expertId) return;
        var card = root.querySelector('.dx-item[data-expert-id="' + expertId + '"], .dx-card[data-expert-id="' + expertId + '"]');
        if (!card) return;
        card.classList.add('is-active');
        if (scroll) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    root.addEventListener('mouseover', function (e) {
        var card = e.target.closest('.dx-item, .dx-card');
        if (!card || !root.contains(card)) return;
        var map = api();
        if (map && map.highlightExpert) map.highlightExpert(card.getAttribute('data-expert-id'));
    });

    var listEl = root.querySelector('.dir-explorer__list');
    if (listEl) {
        listEl.addEventListener('mouseleave', function () {
            var map = api();
            if (map && map.clearHighlight) map.clearHighlight();
        });
    }

    root.addEventListener('click', function (e) {
        var focusBtn = e.target.closest('[data-focus-map]');
        var card = e.target.closest('.dx-item, .dx-card');
        if (focusBtn && card) {
            e.preventDefault();
            e.stopPropagation();
            setActive(card.getAttribute('data-expert-id'), false);
            var map = api();
            if (map && map.focusExpert) map.focusExpert(card.getAttribute('data-expert-id'));
            return;
        }
        if (card && !e.target.closest('a')) {
            setActive(card.getAttribute('data-expert-id'), false);
            var map2 = api();
            if (map2 && map2.highlightExpert) map2.highlightExpert(card.getAttribute('data-expert-id'));
        }
    });

    window.__NE_DIR_SYNC = window.__NE_DIR_SYNC || {};
    window.__NE_DIR_SYNC[mapId] = {
        activateExpert: function (expertId) {
            setActive(expertId, true);
        }
    };
})();
</script>
@endpush
@endsection
