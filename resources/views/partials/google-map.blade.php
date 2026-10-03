{{-- Shared Google Maps partial. Expects: $mapId, $markers, $mapsApiKey, optional $height, optional $interactive --}}
@php
    $mapId = $mapId ?? 'googleMap';
    $markers = $markers ?? [];
    $height = $height ?? '560px';
    $interactive = $interactive ?? false;
@endphp

<div class="gmap-shell @if(($height ?? '') === '100%') gmap-shell--fill @endif" style="--gmap-h: {{ $height }};">
    @if (empty($mapsApiKey))
        <div class="gmap-fallback">
            <div>
                <p class="gmap-fallback__title">Schweiz-Karte</p>
                <p class="gmap-fallback__lead">
                    {{ count($markers) }} Standort{{ count($markers) === 1 ? '' : 'e' }} vorbereitet.
                    Sobald der Google Maps API-Schlüssel konfiguriert ist, erscheinen Marker mit Link zum Profil.
                </p>
                @if (count($markers) > 0)
                    <ul class="gmap-fallback__list">
                        @foreach (array_slice($markers, 0, 8) as $m)
                            <li>
                                <a href="{{ $m['url'] }}">{{ $m['name'] }}</a>
                                <span>{{ $m['city'] }}@if(!empty($m['canton'])) · {{ $m['canton'] }}@endif</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @else
        <div id="{{ $mapId }}" class="gmap-canvas" role="region" aria-label="Expertenskarte"></div>
        <script>
            window.__NE_MAPS = window.__NE_MAPS || {};
            window.__NE_MAPS["{{ $mapId }}"] = {
                markers: @json($markers),
                center: { lat: 46.8182, lng: 8.2275 },
                zoom: 7,
                interactive: @json((bool) $interactive)
            };
        </script>
        <script>
            (function () {
                var mapId = @json($mapId);

                function esc(s) {
                    return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function pinIcon(m, active) {
                    var promoted = !!m.promoted;
                    var fill = active ? '#009DE1' : (promoted ? '#0E4971' : '#009DE1');
                    var ring = active ? '#7fd0f5' : '#ffffff';
                    var letter = esc((m.initial || (m.name || '?').charAt(0)).toString().slice(0, 1).toUpperCase());
                    var scale = active ? 1.18 : 1;
                    var w = Math.round(36 * scale);
                    var h = Math.round(46 * scale);
                    var svg =
                        '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 36 46">' +
                        '<defs><filter id="s" x="-20%" y="-10%" width="140%" height="140%">' +
                        '<feDropShadow dx="0" dy="2" stdDeviation="1.6" flood-color="#062338" flood-opacity="0.35"/>' +
                        '</filter></defs>' +
                        '<path filter="url(#s)" d="M18 1.5c-8.3 0-15 6.5-15 14.5 0 10.4 12.2 26.2 14.2 28.7a1.1 1.1 0 0 0 1.7 0C20.8 42.2 33 26.4 33 16 33 8 26.3 1.5 18 1.5z" fill="' + fill + '" stroke="' + ring + '" stroke-width="2.2"/>' +
                        '<circle cx="18" cy="16" r="9.2" fill="#ffffff"/>' +
                        '<text x="18" y="20.2" text-anchor="middle" font-family="Manrope,Helvetica,Arial,sans-serif" font-size="11" font-weight="800" fill="' + fill + '">' + letter + '</text>' +
                        '</svg>';
                    return {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                        scaledSize: new google.maps.Size(w, h),
                        anchor: new google.maps.Point(w / 2, h - 2)
                    };
                }

                function popupHtml(m) {
                    var tags = '';
                    if (m.buy) tags += '<span class="ne-pop__tag">Kauf</span>';
                    if (m.sell) tags += '<span class="ne-pop__tag">Verkauf</span>';
                    if (m.promoted) tags += '<span class="ne-pop__tag ne-pop__tag--hot">Empfohlen</span>';
                    var media = m.logo
                        ? '<img src="' + esc(m.logo) + '" alt="">'
                        : '<span>' + esc(m.initial || (m.name || '?').charAt(0)) + '</span>';
                    return (
                        '<div class="ne-pop">' +
                        (m.promoted ? '<div class="ne-pop__ribbon">Empfohlen</div>' : '') +
                        '<div class="ne-pop__row">' +
                        '<div class="ne-pop__logo">' + media + '</div>' +
                        '<div class="ne-pop__body">' +
                        '<strong class="ne-pop__name">' + esc(m.name) + '</strong>' +
                        '<div class="ne-pop__meta">' +
                        (m.canton ? '<em>' + esc(m.canton) + '</em>' : '') +
                        esc(m.city || '') +
                        '</div>' +
                        (m.address ? '<div class="ne-pop__addr">' + esc(m.address) + '</div>' : '') +
                        (tags ? '<div class="ne-pop__tags">' + tags + '</div>' : '') +
                        '</div></div>' +
                        '<a class="ne-pop__cta" href="' + esc(m.url) + '">Profil &amp; Anfrage öffnen</a>' +
                        '</div>'
                    );
                }

                function init() {
                    var cfg = (window.__NE_MAPS || {})[mapId];
                    if (!cfg || !window.google || !google.maps) return;
                    var el = document.getElementById(mapId);
                    if (!el || el.getAttribute('data-ne-ready') === '1') return;
                    el.setAttribute('data-ne-ready', '1');

                    var map = new google.maps.Map(el, {
                        center: cfg.center,
                        zoom: cfg.zoom,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                        clickableIcons: false,
                        gestureHandling: 'greedy',
                        styles: [
                            { elementType: 'geometry', stylers: [{ color: '#edf4fa' }] },
                            { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
                            { elementType: 'labels.text.fill', stylers: [{ color: '#2a4f6a' }] },
                            { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }, { weight: 3 }] },
                            { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#a9c7dc' }, { weight: 1 }] },
                            { featureType: 'administrative.country', elementType: 'geometry.stroke', stylers: [{ color: '#0E4971' }, { weight: 1.4 }] },
                            { featureType: 'administrative.land_parcel', stylers: [{ visibility: 'off' }] },
                            { featureType: 'administrative.neighborhood', stylers: [{ visibility: 'off' }] },
                            { featureType: 'landscape', elementType: 'geometry', stylers: [{ color: '#f5f9fc' }] },
                            { featureType: 'landscape.natural', elementType: 'geometry', stylers: [{ color: '#e7f1f8' }] },
                            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                            { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#d5e4ef' }] },
                            { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#d7ebf8' }] },
                            { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#b7d4e8' }] },
                            { featureType: 'road.arterial', elementType: 'labels', stylers: [{ visibility: 'simplified' }] },
                            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#9fc9e6' }] },
                            { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#4f7fa0' }] }
                        ]
                    });

                    var bounds = new google.maps.LatLngBounds();
                    var info = new google.maps.InfoWindow({
                        maxWidth: 320,
                        pixelOffset: new google.maps.Size(0, -8)
                    });
                    var markerIndex = {};
                    var activeExpertId = null;

                    google.maps.event.addListener(info, 'domready', function () {
                        document.querySelectorAll('.gm-style-iw-chr, .gm-ui-hover-effect').forEach(function (node) {
                            node.style.display = 'none';
                        });
                        var iw = document.querySelector('.gm-style-iw-c');
                        if (iw) {
                            iw.classList.add('ne-iw');
                        }
                    });

                    (cfg.markers || []).forEach(function (m) {
                        var pos = { lat: Number(m.lat), lng: Number(m.lng) };
                        var marker = new google.maps.Marker({
                            position: pos,
                            map: map,
                            title: m.name,
                            icon: pinIcon(m, false),
                            optimized: false
                        });
                        marker.__ne = m;

                        marker.addListener('click', function () {
                            info.setContent(popupHtml(m));
                            info.open({ map: map, anchor: marker });
                            if (cfg.interactive && m.expert_id != null) {
                                setActive(String(m.expert_id), false);
                                var sync = (window.__NE_DIR_SYNC || {})[mapId];
                                if (sync && sync.activateExpert) sync.activateExpert(String(m.expert_id));
                            }
                        });

                        marker.addListener('mouseover', function () {
                            if (!cfg.interactive) return;
                            if (m.expert_id != null) setActive(String(m.expert_id), false);
                        });

                        var eid = m.expert_id != null ? String(m.expert_id) : null;
                        if (eid) {
                            if (!markerIndex[eid]) markerIndex[eid] = [];
                            markerIndex[eid].push(marker);
                        }

                        bounds.extend(pos);
                    });

                    function setActive(expertId, pan) {
                        activeExpertId = expertId ? String(expertId) : null;
                        Object.keys(markerIndex).forEach(function (eid) {
                            markerIndex[eid].forEach(function (mk) {
                                var on = activeExpertId && eid === activeExpertId;
                                mk.setIcon(pinIcon(mk.__ne, on));
                                mk.setZIndex(on ? 999 : (mk.__ne.promoted ? 20 : 1));
                            });
                        });
                        if (pan && activeExpertId && markerIndex[activeExpertId] && markerIndex[activeExpertId][0]) {
                            var first = markerIndex[activeExpertId][0];
                            map.panTo(first.getPosition());
                            if (map.getZoom() < 10) map.setZoom(10);
                            info.setContent(popupHtml(first.__ne));
                            info.open({ map: map, anchor: first });
                        }
                    }

                    function clearHighlight() {
                        setActive(null, false);
                    }

                    window.__NE_MAP_API = window.__NE_MAP_API || {};
                    window.__NE_MAP_API[mapId] = {
                        highlightExpert: function (expertId) { setActive(expertId, false); },
                        focusExpert: function (expertId) { setActive(expertId, true); },
                        clearHighlight: clearHighlight,
                        map: map
                    };

                    if ((cfg.markers || []).length > 1) {
                        map.fitBounds(bounds, 56);
                    } else if ((cfg.markers || []).length === 1) {
                        map.setCenter(bounds.getCenter());
                        map.setZoom(11);
                    }

                    setTimeout(function () {
                        google.maps.event.trigger(map, 'resize');
                        if ((cfg.markers || []).length > 1) {
                            map.fitBounds(bounds, 56);
                        }
                    }, 200);
                }

                window['initNeMap_' + mapId] = init;
                if (window.google && google.maps) {
                    init();
                }
            })();
        </script>
        @once
            <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsApiKey) }}&callback=Function.prototype"></script>
            <script>
                (function poll() {
                    if (window.google && google.maps) {
                        Object.keys(window.__NE_MAPS || {}).forEach(function (id) {
                            var fn = window['initNeMap_' + id];
                            if (typeof fn === 'function') fn();
                        });
                        return;
                    }
                    setTimeout(poll, 120);
                })();
            </script>
            <style>
                .gm-style .gm-style-iw-c.ne-iw,
                .gm-style .gm-style-iw-c {
                    padding: 0 !important;
                    border-radius: 2px !important;
                    box-shadow: 0 22px 50px rgba(6, 35, 56, 0.28) !important;
                    border: 1px solid rgba(14, 73, 113, 0.12);
                    overflow: hidden !important;
                }
                .gm-style .gm-style-iw-d {
                    overflow: hidden !important;
                    max-height: none !important;
                }
                .gm-style .gm-style-iw-tc::after,
                .gm-style .gm-style-iw-t::after {
                    background: linear-gradient(45deg, #fff, #fff) !important;
                    box-shadow: none !important;
                }
                .gm-style-iw-chr { display: none !important; }
                .ne-pop {
                    width: 280px;
                    font-family: Manrope, Helvetica, Arial, sans-serif;
                    color: #0a2a42;
                    background: #fff;
                    position: relative;
                }
                .ne-pop__ribbon {
                    position: absolute;
                    top: 0;
                    right: 0;
                    z-index: 1;
                    background: linear-gradient(120deg, #0E4971, #009DE1);
                    color: #fff;
                    font-size: 10px;
                    font-weight: 800;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    padding: 5px 9px;
                }
                .ne-pop__row {
                    display: grid;
                    grid-template-columns: 56px 1fr;
                    gap: 12px;
                    padding: 14px 14px 10px;
                    align-items: start;
                }
                .ne-pop__logo {
                    width: 56px;
                    height: 48px;
                    display: grid;
                    place-items: center;
                    background: linear-gradient(160deg, #f4f8fc, #D0E9FF);
                    border: 1px solid #c5d6e4;
                    overflow: hidden;
                }
                .ne-pop__logo img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    background: #fff;
                }
                .ne-pop__logo span {
                    font-family: "Cormorant Garamond", Georgia, serif;
                    font-size: 22px;
                    font-weight: 600;
                    color: #0E4971;
                }
                .ne-pop__name {
                    display: block;
                    font-size: 15px;
                    line-height: 1.25;
                    font-weight: 750;
                    margin: 0 0 4px;
                    padding-right: 52px;
                }
                .ne-pop__meta {
                    font-size: 12px;
                    color: #5a6b78;
                    line-height: 1.35;
                }
                .ne-pop__meta em {
                    display: inline-block;
                    font-style: normal;
                    margin-right: 5px;
                    padding: 1px 5px;
                    background: #0E4971;
                    color: #fff;
                    font-size: 10px;
                    font-weight: 800;
                    vertical-align: 1px;
                }
                .ne-pop__addr {
                    margin-top: 3px;
                    font-size: 11px;
                    color: #8494a0;
                }
                .ne-pop__tags {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 4px;
                    margin-top: 8px;
                }
                .ne-pop__tag {
                    font-size: 10px;
                    font-weight: 800;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
                    color: #0E4971;
                    background: #D0E9FF;
                    padding: 3px 6px;
                }
                .ne-pop__tag--hot {
                    color: #fff;
                    background: #009DE1;
                }
                .ne-pop__cta {
                    display: block;
                    margin: 0;
                    padding: 11px 14px;
                    text-align: center;
                    text-decoration: none;
                    font-size: 12px;
                    font-weight: 800;
                    letter-spacing: 0.02em;
                    color: #fff !important;
                    background: linear-gradient(120deg, #0E4971, #009DE1);
                }
                .ne-pop__cta:hover {
                    filter: brightness(1.05);
                }
            </style>
        @endonce
    @endif
</div>
