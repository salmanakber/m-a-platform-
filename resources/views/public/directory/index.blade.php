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
        'gruppe' => $group ?? null,
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

            <div class="dx-bar__search xp-address-suggest" data-suggest-root data-dir-suggest>
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <div class="xp-address-suggest__wrap dx-bar__suggest-wrap">
                    <input type="search" name="q" id="q" value="{{ $q }}" placeholder="Firma, Adresse, Ort oder PLZ…" autocomplete="off" data-suggest-input aria-autocomplete="list">
                    <div class="xp-address-suggest__list" data-suggest-list hidden role="listbox"></div>
                </div>
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

            <div class="dx-bar__select">
                <select name="gruppe" id="gruppe" aria-label="Fachgruppe" onchange="this.form.submit()">
                    <option value="">Alle Fachgruppen</option>
                    @foreach (($professionalGroups ?? []) as $groupKey => $groupLabel)
                        <option value="{{ $groupKey }}" @selected(($group ?? '') === $groupKey)>{{ $groupLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dx-bar__end">
                <button type="submit" class="dx-bar__go">Suchen</button>
                @if ($q || $direction || $cantonCode || !empty($group))
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
                            <div class="dx-item__media logo-pos--{{ $expert->logo_position ?? 'left' }}">
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
                                    <span class="is-soft">{{ \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist') }}</span>
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
<script type="application/json" id="dirSuggestConfig">@json([
    'url' => $suggestUrl ?? route('map.suggest'),
    'mapId' => 'directorySplitMap',
])</script>
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
        if (card && !e.target.closest('a') && !e.target.closest('[data-suggest-root]')) {
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

    /* Address / place autosuggest */
    var cfgEl = document.getElementById('dirSuggestConfig');
    var suggestRoot = root.querySelector('[data-dir-suggest]');
    if (!cfgEl || !suggestRoot) return;

    var cfg = {};
    try { cfg = JSON.parse(cfgEl.textContent || '{}'); } catch (e) { return; }
    var suggestUrl = cfg.url;
    var input = suggestRoot.querySelector('[data-suggest-input]');
    var list = suggestRoot.querySelector('[data-suggest-list]');
    var cantonSelect = document.getElementById('kanton');
    var filterForm = root.querySelector('.dx-bar');
    if (!suggestUrl || !input || !list) return;

    var timer = null;
    var lastQ = '';
    var picking = false;

    function focusOnMap(item, label) {
        function go() {
            var map = api();
            if (map && map.focusLocation && item.latitude != null && item.longitude != null) {
                map.focusLocation(item.latitude, item.longitude, label);
                return true;
            }
            return false;
        }
        if (go()) return;
        var tries = 0;
        var wait = setInterval(function () {
            tries++;
            if (go() || tries > 20) clearInterval(wait);
        }, 150);
    }

    function applySuggestion(item) {
        picking = true;
        var searchBits = [item.postal_code, item.city].filter(Boolean);
        var searchValue = searchBits.length ? searchBits.join(' ') : (item.address_line || item.label || input.value);
        input.value = searchValue;

        if (item.canton_code && cantonSelect) {
            var code = String(item.canton_code).toUpperCase();
            var opt = Array.prototype.find.call(cantonSelect.options, function (o) {
                return o.value === code;
            });
            if (opt) cantonSelect.value = code;
        }

        var label = item.label || searchValue;
        list.hidden = true;

        // Persist pin across filter reload
        try {
            if (item.latitude != null && item.longitude != null) {
                sessionStorage.setItem('ne_dir_focus', JSON.stringify({
                    lat: item.latitude,
                    lng: item.longitude,
                    label: label
                }));
            }
        } catch (err) {}

        if (filterForm) {
            filterForm.submit();
            return;
        }
        focusOnMap(item, label);
    }

    // Restore search pin after filter submit
    try {
        var saved = sessionStorage.getItem('ne_dir_focus');
        if (saved) {
            sessionStorage.removeItem('ne_dir_focus');
            var point = JSON.parse(saved);
            focusOnMap({ latitude: point.lat, longitude: point.lng }, point.label || '');
        }
    } catch (err) {}

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
                applySuggestion(item);
            });
            list.appendChild(btn);
        });
        list.hidden = false;
    }

    input.addEventListener('input', function () {
        if (picking) { picking = false; return; }
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
            fetch(suggestUrl + '?q=' + encodeURIComponent(q), {
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
        if (e.key === 'Enter' && !list.hidden) {
            var first = list.querySelector('.xp-address-suggest__item');
            if (first) {
                e.preventDefault();
                first.dispatchEvent(new Event('mousedown'));
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (!suggestRoot.contains(e.target)) list.hidden = true;
    });
})();
</script>
@endpush
@endsection
