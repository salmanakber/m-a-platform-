@extends('layouts.public')

@section('title', 'Karte — nachfolge-experten.ch')
@section('meta_description', 'Schweizer M&A-Experten und Events auf der Karte finden — Standorte, Kantone, Veranstaltungen.')
@section('flush', true)
@section('body_class', 'is-map-page')

@section('content')
@php
    $queryBase = array_filter([
        'richtung' => $direction ?? null,
        'kanton' => $cantonCode ?: null,
        'gruppe' => $group ?? null,
        'events' => ($showEvents ?? true) ? null : '0',
    ], fn ($v) => $v !== null && $v !== '');
    $listUrl = route('directory.index', array_merge($queryBase, ['ansicht' => 'liste']));
    $markerCount = count($markers ?? []);
    $eventCount = ($mapEvents ?? collect())->count();
    $officeCount = ($offices ?? collect())->count();
    $groupColors = \App\Support\ProfessionalGroup::colors();
    $groupLabels = \App\Support\ProfessionalGroup::mapLabels();
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
                <h1 class="map-hud__title">Experten auf der Karte</h1>
                <p class="map-hud__count">
                    <strong>{{ $markerCount }}</strong> Marker
                    @if ($officeCount) · {{ $officeCount }} Experten @endif
                    @if ($eventCount) · {{ $eventCount }} Events @endif
                    @if ($cantonCode) · {{ $cantonCode }} @endif
                </p>
            </div>
            <div class="map-hud__top-actions">
                <a class="map-hud__back" href="{{ $listUrl }}">← Zur Expertenliste</a>
                <nav class="map-hud__views" aria-label="Ansicht">
                    <a href="{{ $listUrl }}">Liste</a>
                    <a class="is-active" href="{{ route('map.index', $queryBase) }}">Karte</a>
                    <a href="{{ route('events.index') }}">Events</a>
                </nav>
            </div>
        </header>

        <form method="get" class="map-hud__filters" action="{{ route('map.index') }}" id="mapFiltersForm">
            <div class="map-hud__address xp-address-suggest" data-suggest-root data-map-suggest>
                <div class="xp-address-suggest__wrap">
                    <input
                        class="form-control"
                        type="search"
                        id="mapAddressSearch"
                        name="ort"
                        value="{{ request('ort') }}"
                        placeholder="Adresse, Ort oder PLZ suchen…"
                        autocomplete="off"
                        data-suggest-input
                        aria-label="Adresse suchen"
                    >
                    <div class="xp-address-suggest__list" data-suggest-list hidden role="listbox"></div>
                </div>
            </div>
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
                <select class="form-control" name="kanton" id="mapCantonSelect" aria-label="Kanton" onchange="this.form.submit()">
                    <option value="">Alle Kantone</option>
                    @foreach (\App\Models\Canton::query()->orderBy('code')->get() as $canton)
                        <option value="{{ $canton->code }}" @selected(($cantonCode ?? '') === $canton->code)>{{ $canton->code }} — {{ $canton->name_de }}</option>
                    @endforeach
                </select>
            </div>
            <div class="map-hud__select">
                <select class="form-control" name="gruppe" aria-label="Fachgruppe" onchange="this.form.submit()">
                    <option value="">Alle Fachgruppen</option>
                    @foreach ($groupLabels as $gKey => $gLabel)
                        <option value="{{ $gKey }}" @selected(($group ?? '') === $gKey)>{{ $gLabel }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="events" value="0">
            <label class="map-hud__chip {{ ($showEvents ?? true) ? 'is-active' : '' }}" style="margin-left:auto;">
                <input type="checkbox" name="events" value="1" @checked($showEvents ?? true) onchange="this.form.submit()"> Events
            </label>
            <div class="map-hud__legend" aria-label="Legende">
                @foreach ($groupLabels as $gKey => $gLabel)
                    <span><i class="map-legend__dot" style="background:{{ $groupColors[$gKey] }}"></i> {{ $gLabel }}</span>
                @endforeach
                <span><i class="map-legend__dot map-legend__dot--promo"></i> Empfohlen</span>
                <span><i class="map-legend__dot map-legend__dot--event"></i> Event</span>
            </div>
        </form>

        @if (($offices ?? collect())->isNotEmpty() || ($mapEvents ?? collect())->isNotEmpty())
            <aside class="map-hud__panel" aria-label="Treffer">
                <div class="map-hud__panel-head">
                    <div>
                        <p class="map-hud__kicker">Treffer</p>
                        <h2>{{ $markerCount }} auf der Karte</h2>
                    </div>
                    <a class="map-hud__back map-hud__back--panel" href="{{ $listUrl }}">← Zur Liste</a>
                </div>
                <div class="map-hud__panel-list">
                    @foreach ($offices as $office)
                        @continue(!($office->expert))
                        @php
                            $isPromoted = isset($promotedIds) && $promotedIds->has($office->expert_id);
                            $gKey = $office->expert->professional_group ?: \App\Support\ProfessionalGroup::MA_SPECIALIST;
                            $gColor = \App\Support\ProfessionalGroup::color($gKey);
                        @endphp
                        <a class="map-hud__row @if($isPromoted) is-promoted @endif" href="{{ route('experts.show', $office->expert->slug) }}">
                            <span class="map-hud__pin" style="background:{{ $gColor }};box-shadow:0 0 0 3px {{ $gColor }}33;" aria-hidden="true"></span>
                            <span class="map-hud__row-body">
                                <strong>{{ $office->expert->company_name }}</strong>
                                <em>{{ \App\Support\ProfessionalGroup::label($gKey) }} · {{ $office->city }}@if ($office->canton) · {{ $office->canton->code }}@endif</em>
                            </span>
                            @if ($isPromoted)<span class="map-hud__badge">Empfohlen</span>@endif
                        </a>
                    @endforeach

                    @foreach (($mapEvents ?? collect()) as $event)
                        <a class="map-hud__row is-event" href="{{ route('events.show', $event->slug) }}">
                            <span class="map-hud__pin map-hud__pin--event" aria-hidden="true"></span>
                            <span class="map-hud__row-body">
                                <strong>{{ $event->title }}</strong>
                                <em>
                                    Event
                                    @if ($event->starts_on) · {{ $event->starts_on->format('d.m.Y') }} @endif
                                    @if ($event->city) · {{ $event->city }} @endif
                                    @if ($event->canton) · {{ $event->canton->code }} @endif
                                </em>
                            </span>
                            <span class="map-hud__badge map-hud__badge--event">Event</span>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif
    </div>
</div>

<script type="application/json" id="mapSuggestConfig">@json([
    'url' => $suggestUrl ?? route('map.suggest'),
    'mapId' => 'switzerlandMap',
])</script>
<script>
(function () {
    var cfgEl = document.getElementById('mapSuggestConfig');
    if (!cfgEl) return;
    var cfg = {};
    try { cfg = JSON.parse(cfgEl.textContent || '{}'); } catch (e) { return; }
    var url = cfg.url;
    var mapId = cfg.mapId || 'switzerlandMap';
    var root = document.querySelector('[data-map-suggest]');
    if (!root || !url) return;

    var input = root.querySelector('[data-suggest-input]');
    var list = root.querySelector('[data-suggest-list]');
    if (!input || !list) return;

    var timer = null;
    var lastQ = '';
    var suppressSubmit = false;

    function mapApi() {
        return (window.__NE_MAP_API || {})[mapId] || null;
    }

    function focusItem(item) {
        var label = item.label || [item.address_line, item.postal_code, item.city].filter(Boolean).join(', ');
        input.value = label;
        list.hidden = true;

        if (item.canton_code) {
            var cantonSelect = document.getElementById('mapCantonSelect');
            if (cantonSelect) {
                var code = String(item.canton_code).toUpperCase();
                var opt = Array.prototype.find.call(cantonSelect.options, function (o) {
                    return o.value === code;
                });
                if (opt && cantonSelect.value !== code) {
                    // Keep current filter; only pan — changing canton would reload and lose focus.
                }
            }
        }

        var api = mapApi();
        if (api && api.focusLocation && item.latitude != null && item.longitude != null) {
            api.focusLocation(item.latitude, item.longitude, label);
            return;
        }

        // Map not ready yet — retry briefly
        var tries = 0;
        var wait = setInterval(function () {
            tries++;
            var a = mapApi();
            if (a && a.focusLocation && item.latitude != null && item.longitude != null) {
                clearInterval(wait);
                a.focusLocation(item.latitude, item.longitude, label);
            } else if (tries > 20) {
                clearInterval(wait);
            }
        }, 150);
    }

    function renderList(items) {
        list.innerHTML = '';
        if (!items.length) {
            list.hidden = true;
            return;
        }
        items.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'xp-address-suggest__item';
            btn.setAttribute('role', 'option');
            btn.innerHTML = '<strong></strong><span></span>';
            btn.querySelector('strong').textContent = item.address_line || item.city || item.label || 'Adresse';
            var meta = [item.postal_code, item.city, item.canton_code].filter(Boolean).join(' · ');
            btn.querySelector('span').textContent = meta || item.label || '';
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                suppressSubmit = true;
                focusItem(item);
            });
            list.appendChild(btn);
        });
        list.hidden = false;
    }

    input.addEventListener('input', function () {
        var q = (input.value || '').trim();
        if (timer) clearTimeout(timer);
        if (q.length < 3) {
            list.hidden = true;
            list.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            if (q === lastQ) return;
            lastQ = q;
            list.innerHTML = '<div class="xp-address-suggest__loading">Suche Adressen…</div>';
            list.hidden = false;
            fetch(url + '?q=' + encodeURIComponent(q), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); })
              .then(function (data) {
                  if ((input.value || '').trim() !== q) return;
                  renderList(data.suggestions || []);
              }).catch(function () {
                  list.innerHTML = '<div class="xp-address-suggest__loading">Keine Vorschläge verfügbar</div>';
              });
        }, 280);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') list.hidden = true;
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = list.querySelector('.xp-address-suggest__item');
            if (first && !list.hidden) first.dispatchEvent(new Event('mousedown'));
        }
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) list.hidden = true;
    });

    var form = document.getElementById('mapFiltersForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (suppressSubmit || document.activeElement === input) {
                e.preventDefault();
                suppressSubmit = false;
            }
        });
    }
})();
</script>
@endsection
