@extends('layouts.expert')

@section('title', 'Standorte')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Standorte</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Profil</p>
            <h1>Standorte &amp; Adresse</h1>
            <p>Hinterlegen Sie Ihre Geschäftsadresse — daraus werden Kartenmarker (Lat/Lng) für das öffentliche Verzeichnis gesetzt.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('expert.profile.edit') }}">Unternehmen</a>
        </div>
    </header>

    <div class="xp-action-strip">
        <div>
            <strong>Wichtig für die Karte</strong>
            <p>Ohne Adresse und Kanton erscheint Ihr Profil nicht korrekt auf der Karte. Speichern mit «Koordinaten berechnen» aktiviert.</p>
        </div>
    </div>

    @if ($offices->isNotEmpty())
        <div class="xp-office-list">
            @foreach ($offices as $office)
                <section class="xp-panel">
                    <div class="xp-panel__head">
                        <div>
                            <h2>{{ $office->label ?: 'Standort' }}@if($office->is_primary) <span class="pill pill--success">Hauptstandort</span>@endif</h2>
                            <p>
                                {{ $office->address_line }} · {{ $office->postal_code }} {{ $office->city }}
                                @if ($office->canton) · {{ $office->canton->code }} @endif
                            </p>
                            <p class="xp-muted" style="margin:0.35rem 0 0;">
                                @if ($office->latitude && $office->longitude)
                                    Karte: {{ number_format((float) $office->latitude, 5, '.', '') }}, {{ number_format((float) $office->longitude, 5, '.', '') }}
                                @else
                                    Noch keine Koordinaten — bitte Adresse speichern und Geocoding aktivieren.
                                @endif
                            </p>
                        </div>
                    </div>
                    <details class="xp-office-edit">
                        <summary>Adresse bearbeiten</summary>
                        <form method="post" action="{{ route('expert.offices.update', $office) }}" class="xp-office-edit__form">
                            @csrf
                            @method('PUT')
                            @include('expert.offices._fields', ['office' => $office])
                            <div class="xp-article-panel__actions" style="margin-top:0.75rem;">
                                <button type="submit" class="btn">Standort speichern</button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('expert.offices.destroy', $office) }}" onsubmit="return confirm('Standort löschen?');" class="xp-danger-strip" style="margin-top:1rem;">
                            @csrf
                            @method('DELETE')
                            <div><strong>Standort löschen</strong><p>Entfernt Adresse und Kartenmarker.</p></div>
                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                        </form>
                    </details>
                </section>
            @endforeach
        </div>
    @else
        <div class="xp-empty">
            <strong>Noch keine Adresse hinterlegt</strong>
            <p>Fügen Sie unten Ihren Hauptsitz hinzu, damit Interessenten Sie auf der Karte finden.</p>
        </div>
    @endif

    <section class="xp-panel" style="margin-top:1.15rem;">
        <div class="xp-panel__head">
            <div>
                <h2>{{ $offices->isEmpty() ? 'Hauptsitz erfassen' : 'Weiteren Standort hinzufügen' }}</h2>
                <p>Adresse tippen und Vorschlag wählen — PLZ, Ort, Kanton und Karte werden übernommen.</p>
            </div>
        </div>
        <form method="post" action="{{ route('expert.offices.store') }}" style="padding:0 1.25rem 1.25rem;">
            @csrf
            @include('expert.offices._fields', ['office' => null])
            <button type="submit" class="btn" style="margin-top:0.5rem;">Adresse speichern</button>
        </form>
    </section>
</div>

<script type="application/json" id="officeSuggestConfig">@json([
    'url' => $suggestUrl,
    'cantons' => $cantonCodeMap,
])</script>
<script>
(function () {
    var cfgEl = document.getElementById('officeSuggestConfig');
    if (!cfgEl) return;
    var cfg = {};
    try { cfg = JSON.parse(cfgEl.textContent || '{}'); } catch (e) { return; }
    var url = cfg.url;
    var cantonMap = cfg.cantons || {};

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function closestRoot(el) {
        return el.closest('[data-suggest-root]');
    }

    function fillForm(root, item) {
        var address = root.querySelector('[name="address_line"]');
        var postal = root.closest('form').querySelector('[name="postal_code"]');
        var city = root.closest('form').querySelector('[name="city"]');
        var canton = root.closest('form').querySelector('[name="canton_id"]');
        var lat = root.closest('form').querySelector('[name="latitude"]');
        var lng = root.closest('form').querySelector('[name="longitude"]');
        var geocode = root.closest('form').querySelector('[name="geocode"]');

        if (address && item.address_line) address.value = item.address_line;
        else if (address && item.label) address.value = item.label.split(',')[0].trim();
        if (postal && item.postal_code) postal.value = item.postal_code;
        if (city && item.city) city.value = item.city;
        if (canton && item.canton_code && cantonMap[item.canton_code]) {
            canton.value = String(cantonMap[item.canton_code]);
        }
        if (lat && item.latitude != null) lat.value = item.latitude;
        if (lng && item.longitude != null) lng.value = item.longitude;
        if (geocode && item.latitude != null && item.longitude != null) geocode.checked = false;
    }

    function renderList(listEl, items, root) {
        listEl.innerHTML = '';
        if (!items.length) {
            listEl.hidden = true;
            return;
        }
        items.forEach(function (item, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'xp-address-suggest__item';
            btn.setAttribute('role', 'option');
            btn.innerHTML = '<strong></strong><span></span>';
            btn.querySelector('strong').textContent = item.address_line || item.label || 'Adresse';
            var meta = [item.postal_code, item.city, item.canton_code].filter(Boolean).join(' · ');
            btn.querySelector('span').textContent = meta || item.label || '';
            btn.addEventListener('click', function () {
                fillForm(root, item);
                listEl.hidden = true;
            });
            listEl.appendChild(btn);
        });
        listEl.hidden = false;
    }

    document.querySelectorAll('[data-suggest-root]').forEach(function (root) {
        var input = root.querySelector('[data-suggest-input]');
        var list = root.querySelector('[data-suggest-list]');
        if (!input || !list) return;
        var timer = null;
        var lastQ = '';

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
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf()
                    },
                    credentials: 'same-origin'
                }).then(function (r) { return r.json(); })
                  .then(function (data) {
                      if ((input.value || '').trim() !== q) return;
                      renderList(list, data.suggestions || [], root);
                  }).catch(function () {
                      list.innerHTML = '<div class="xp-address-suggest__loading">Keine Vorschläge verfügbar</div>';
                  });
            }, 320);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') list.hidden = true;
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) list.hidden = true;
        });
    });
})();
</script>
@endsection
