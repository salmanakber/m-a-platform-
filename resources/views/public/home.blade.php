@extends('layouts.public')

@section('title', 'nachfolge-experten.ch — M&A und Unternehmensnachfolge')
@section('meta_description', 'Das Schweizer Verzeichnis für M&A- und Nachfolge-Experten — geprüft, kantonal, diskret. Finden Sie den passenden Spezialisten für Verkauf, Bewertung oder Nachfolge.')
@section('hero')
<section class="nh-hero" aria-label="Einstieg">
    <div class="nh-hero__grid" aria-hidden="true"></div>
    <div class="nh-hero__glow" aria-hidden="true"></div>
    <div class="nh-hero__inner">
        <div class="nh-hero__copy">
            <p class="nh-hero__eyebrow"><span></span> Schweiz · M&A · Nachfolge — Verzeichnis {{ date('Y') }}</p>
            <h1 class="nh-hero__title">
                Ihr Lebenswerk.<br>
                <em>In neuen, guten Händen.</em>
            </h1>
            <p class="nh-hero__lead">Das Schweizer Verzeichnis für M&A- und Nachfolge-Experten — geprüft, kantonal, diskret.</p>

            <form class="nh-hero-search" method="get" action="{{ route('directory.index') }}" role="search" aria-label="Experten finden">
                <p class="nh-hero-search__label">Ich möchte verkaufen — wer passt zu mir?</p>
                <div class="nh-hero-search__row">
                    <div class="nh-hero-search__field">
                        <label for="hero_kanton">Kanton</label>
                        <select class="form-control" name="kanton" id="hero_kanton" required>
                            <option value="">Kanton wählen</option>
                            @foreach ($cantons as $canton)
                                <option value="{{ $canton->code }}" @selected(($selectedCanton->code ?? '') === $canton->code)>{{ $canton->code }} — {{ $canton->name_de }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nh-hero-search__field">
                        <label for="hero_ziel">Ziel</label>
                        <select class="form-control" name="ziel" id="hero_ziel" required>
                            <option value="">Ziel wählen</option>
                            @foreach ($searchObjectives as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="nh-btn nh-btn--gold nh-hero-search__btn">Passende Experten finden</button>
                </div>
            </form>

            <div class="nh-hero__actions">
                <a class="nh-btn nh-btn--ghost" href="#top5">Top 5 ansehen ↓</a>
                <a class="nh-btn nh-btn--ghost" href="{{ route('map.index') }}">Schweiz-Karte ↗</a>
            </div>
        </div>

        <aside class="nh-match" aria-label="Empfohlene Experten" data-banner-slider>
            @if (($bannerCards ?? collect())->isNotEmpty())
                <div class="nh-match__chrome">
                    <div class="nh-match__badge">
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M12 2l2.4 4.9L20 8.2l-3.6 3.5.9 5.2L12 14.8 6.7 16.9l.9-5.2L4 8.2l5.6-1.3L12 2z"/></svg>
                        Empfohlen · {{ $periodLabel }}
                    </div>
                    @if ($bannerCards->count() > 1)
                        <div class="nh-match__nav">
                            <button type="button" class="nh-match__arrow" data-banner-prev aria-label="Vorheriger">
                                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M15.4 7.4 14 6l-6 6 6 6 1.4-1.4L10.8 12z"/></svg>
                            </button>
                            <button type="button" class="nh-match__arrow" data-banner-next aria-label="Nächster">
                                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="m10 6-1.4 1.4L12.2 12l-3.6 4.6L10 18l6-6z"/></svg>
                            </button>
                        </div>
                    @endif
                </div>

                <div class="nh-match__viewport">
                    <div class="nh-match__slides" data-banner-track>
                        @foreach ($bannerCards as $index => $card)
                            <div class="nh-match__slide {{ $index === 0 ? 'is-active' : '' }}" data-banner-slide @if($index !== 0) hidden @endif>
                                <div class="nh-match__row">
                                    @if ($card['logo'])
                                        <span class="nh-avatar nh-avatar--logo"><img src="{{ $card['logo'] }}" alt=""></span>
                                    @else
                                        <span class="nh-avatar" style="--g:{{ $card['color'] }}">{{ $card['initials'] }}</span>
                                    @endif
                                    <div>
                                        <strong>{{ $card['display_name'] }}</strong>
                                        @if (!empty($card['person_name']))
                                            <span>{{ $card['person_name'] }}</span>
                                        @elseif (!empty($card['place_line']))
                                            <span>{{ $card['place_line'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="nh-match__meta">
                                    @if (!empty($card['is_operator']))
                                        <em class="nh-op-tag">Plattform-Betreiber</em>
                                    @elseif ($card['rating'])
                                        <em>★ {{ $card['rating'] }} / 5.0</em>
                                    @endif
                                    <span>{{ $card['place_line'] }}</span>
                                </div>
                                <a class="nh-btn nh-btn--ghost nh-btn--block" href="{{ $card['url'] }}">Profil ansehen ↗</a>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($bannerCards->count() > 1)
                    <div class="nh-match__dots" data-banner-dots role="tablist" aria-label="Empfohlene Experten">
                        @foreach ($bannerCards as $index => $card)
                            <button type="button" class="nh-match__dot {{ $index === 0 ? 'is-on' : '' }}" data-banner-dot="{{ $index }}" aria-label="Slide {{ $index + 1 }}">
                                <span class="nh-match__dot-fill" data-banner-dot-fill></span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="nh-match__badge">Empfohlen</div>
                <p class="nh-match__empty">Nutzen Sie die Suche links — oder entdecken Sie alle geprüften Profile im Verzeichnis.</p>
                <a class="nh-btn nh-btn--ghost nh-btn--block" href="{{ route('directory.index') }}">Expertenverzeichnis ↗</a>
            @endif
        </aside>
    </div>

    <div class="nh-stats">
        <div><b>{{ number_format($stats['experts']) }}</b><span>Geprüfte Experten</span></div>
        @if (!empty($stats['cantons_covered']))
            <div><b>{{ number_format($stats['cantons_covered']) }}</b><span>Kantone abgedeckt</span></div>
        @endif
        @if (!empty($stats['deals']))
            <div><b>{{ number_format($stats['deals']) }}</b><span>Begleitete Transaktionen</span></div>
        @endif
        @if (!empty($stats['rating']))
            <div><b>Ø {{ number_format($stats['rating'], 1) }}</b><span>Kundenbewertung</span></div>
        @endif
    </div>
</section>
@endsection

@section('content')
<div class="nh-page">

    <div class="nh-ticker" aria-hidden="true">
        <div class="nh-ticker__track">
            <span>Unternehmensverkauf</span><i>+</i>
            <span>Nachfolgeplanung</span><i>+</i>
            <span>KMU-Bewertung</span><i>+</i>
            <span>Due Diligence</span><i>+</i>
            <span>MBO / MBI</span><i>+</i>
            <span>Cross-Border</span><i>+</i>
            <span>Steueroptimierung</span><i>+</i>
            <span>Unternehmensverkauf</span><i>+</i>
            <span>Nachfolgeplanung</span><i>+</i>
            <span>KMU-Bewertung</span><i>+</i>
            <span>Due Diligence</span><i>+</i>
            <span>MBO / MBI</span><i>+</i>
        </div>
    </div>

    {{-- Top 5 --}}
    <section class="nh-section" id="top5">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Rangliste · {{ $periodLabel }}</p>
                <h2>Top 5 Experten der Schweiz</h2>
                <p>Die führenden M&A- und Nachfolge-Spezialisten — manuell kuratiert, mit Rang und Gültigkeitsdauer.</p>
            </div>
            <div class="nh-section__tools">
                @if ($nationalCards->isNotEmpty())
                    <div class="nh-slider-nav" data-top5-nav>
                        <button type="button" data-top5-prev aria-label="Zurück">‹</button>
                        <button type="button" data-top5-next aria-label="Weiter">›</button>
                    </div>
                @endif
                <a class="nh-link" href="{{ route('directory.index') }}">Alle Experten →</a>
            </div>
        </div>

        @if ($nationalCards->isEmpty())
            <div class="nh-empty">
                <strong>Noch keine Top-5-Liste.</strong>
                <p>Die nationale Top-5-Liste wird in Kürze veröffentlicht. Bis dahin finden Sie alle geprüften Profile im Verzeichnis.</p>
            </div>
        @else
            <div class="nh-top-slider" data-top5-slider>
                <div class="nh-top-slider__track" data-top5-track>
                    @foreach ($nationalCards as $card)
                        <div class="nh-top-slider__item">
                            @include('public.partials.nh-expert-card', ['card' => $card])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- Marktplätze (after Experten) --}}
    <section class="nh-band nh-band--soft" id="marktplaetze">
        <div class="nh-section">
            <div class="nh-section__head">
                <div>
                    <p class="nh-kicker">Fachgruppe</p>
                    <h2>Marktplätze</h2>
                    <p>Digitale Plattformen und Marktplätze für Unternehmensnachfolge und M&A in der Schweiz.</p>
                </div>
                <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'marktplatz']) }}">Alle Marktplätze →</a>
            </div>
            @if ($marktplatzCards->isEmpty())
                <div class="nh-empty">Derzeit sind noch keine Marktplätze veröffentlicht.</div>
            @else
                <div class="nh-topgrid">
                    @foreach ($marktplatzCards as $card)
                        @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => 'Marktplatz'])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Top 3 Kanton --}}
    <section class="nh-section" id="kantone-top">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Regional</p>
                <h2>Top 3 Experten je Kanton</h2>
                <p>Wählen Sie Ihren Kanton — sehen Sie aktive Experten vor Ort und die kuratierte Top 3.</p>
            </div>
            <a class="nh-link" href="{{ route('cantons.index') }}">Alle Kantone →</a>
        </div>

        @if (($cantonTiles ?? collect())->isNotEmpty())
            <div class="nh-discover-cantons" aria-label="Kantons-Auswahl">
                @foreach ($cantonTiles as $tile)
                    <a class="nh-discover-cantons__tile {{ ($tile['count'] ?? 0) > 0 ? 'is-live' : '' }} {{ !empty($tile['selected']) ? 'is-selected' : '' }}"
                       href="{{ route('home', ['kanton' => $tile['code']]) }}#kantone-top"
                       @if(!empty($tile['selected'])) aria-current="true" @endif>
                        <b>{{ $tile['code'] }}</b>
                        <span>{{ $tile['name'] }}</span>
                        <em>{{ ($tile['count'] ?? 0) > 0 ? $tile['count'] : '—' }}</em>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="nh-canton nh-canton--single">
            <div class="nh-canton__main">
                <div class="nh-canton__title">
                    <h3>Top 3 in {{ $selectedCanton->name_de ?? '—' }}</h3>
                    <span>Kanton {{ $selectedCanton->code ?? '—' }} · vorausgewählt</span>
                </div>

                @php $cs = $selectedCantonStats ?? ['total' => 0, 'ma' => 0, 'jurist' => 0, 'other' => 0]; @endphp
                <div class="nh-canton__stats" aria-label="Aktive Experten in diesem Kanton">
                    <span><b>{{ $cs['total'] }}</b> aktive Experten</span>
                    @if (($cs['ma'] ?? 0) > 0)
                        <span><b>{{ $cs['ma'] }}</b> M&A-Spezialisten</span>
                    @endif
                    @if (($cs['jurist'] ?? 0) > 0)
                        <span><b>{{ $cs['jurist'] }}</b> Juristen</span>
                    @endif
                    @if (($cs['other'] ?? 0) > 0)
                        <span><b>{{ $cs['other'] }}</b> Weitere</span>
                    @endif
                </div>

                @if ($cantonCards->isEmpty())
                    <div class="nh-empty nh-empty--panel">
                        <strong>Noch keine Top-3-Ränge in {{ $selectedCanton->name_de ?? 'diesem Kanton' }}.</strong>
                        <p>
                            @if (($cs['total'] ?? 0) > 0)
                                In diesem Kanton sind {{ $cs['total'] }} geprüfte Experten aktiv — eine kuratierte Top-3-Liste folgt.
                            @else
                                In diesem Kanton sind derzeit keine geprüften Experten gelistet.
                            @endif
                        </p>
                        <a class="nh-btn nh-btn--navy" href="{{ route('directory.index', ['kanton' => $selectedCanton->code ?? '']) }}">Im Verzeichnis ansehen ↗</a>
                    </div>
                @else
                    <div class="nh-canton__cards">
                        @foreach ($cantonCards as $card)
                            <article class="nh-rankrow">
                                <span class="nh-rankrow__n">{{ $card['n'] }}</span>
                                @if ($card['logo'])
                                    <span class="nh-avatar nh-avatar--sm nh-avatar--logo"><img src="{{ $card['logo'] }}" alt=""></span>
                                @else
                                    <span class="nh-avatar nh-avatar--sm" style="--g:{{ $card['color'] }}">{{ $card['initials'] }}</span>
                                @endif
                                <div class="nh-rankrow__copy">
                                    <strong>{{ $card['display_name'] }}</strong>
                                    @if (!empty($card['person_name']))
                                        <span>{{ $card['person_name'] }}</span>
                                    @endif
                                    @if (!empty($card['is_operator']))
                                        <span class="nh-op-tag">Plattform-Betreiber</span>
                                    @endif
                                </div>
                                <div class="nh-rankrow__meta">
                                    @if ($card['rating'])
                                        <em>★ {{ $card['rating'] }}</em>
                                    @endif
                                    @if ($card['deals'])
                                        <span>{{ $card['deals'] }} Deals</span>
                                    @endif
                                </div>
                                <a class="nh-rankrow__link" href="{{ $card['url'] }}">Profil →</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Fachbeiträge --}}
    <section class="nh-band nh-band--dark" id="fachbeitraege">
        <div class="nh-section nh-section--flush">
            <div class="nh-section__head nh-section__head--light">
                <div>
                    <p class="nh-kicker">Wissen</p>
                    <h2>Fachbeiträge zur Nachfolge</h2>
                    <p>Aktuelle Beiträge von Experten zu Verkauf, Bewertung und Nachfolge.</p>
                </div>
                <a class="nh-link nh-link--light" href="{{ route('blog.index') }}">Alle Beiträge →</a>
            </div>

            @if ($articleTopCards->isNotEmpty())
                <div class="nh-articles-top">
                    @foreach ($articleTopCards as $article)
                        <a class="nh-article-card" href="{{ $article['url'] }}">
                            <span class="nh-article-card__n">{{ $article['n'] }}</span>
                            <h3>{{ $article['title'] }}</h3>
                            <p>{{ $article['excerpt'] }}</p>
                            <em>Lesen →</em>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($articleCarouselCards->isNotEmpty())
                <div class="nh-carousel" data-carousel>
                    <button type="button" class="nh-carousel__btn" data-carousel-prev aria-label="Zurück">‹</button>
                    <div class="nh-carousel__track" data-carousel-track>
                        @foreach ($articleCarouselCards as $article)
                            <a class="nh-carousel__item" href="{{ $article['url'] }}">
                                <span>{{ $article['n'] }}</span>
                                <strong>{{ $article['title'] }}</strong>
                                <em>Lesen →</em>
                            </a>
                        @endforeach
                    </div>
                    <button type="button" class="nh-carousel__btn" data-carousel-next aria-label="Weiter">›</button>
                </div>
            @elseif ($articleTopCards->isEmpty())
                <div class="nh-empty nh-empty--dark">Noch keine Fachbeiträge veröffentlicht.</div>
            @endif
        </div>
    </section>

    {{-- Events --}}
    <section class="nh-section" id="events">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Veranstaltungen</p>
                <h2>Events &amp; Webinare</h2>
                <p>Kommende Veranstaltungen zu Nachfolge und M&A — Anmeldung geht direkt an den Experten.</p>
            </div>
            <a class="nh-link" href="{{ route('events.index') }}">Alle Events →</a>
        </div>
        @if ($upcomingEvents->isEmpty())
            <div class="nh-empty">Aktuell keine kommenden Events.</div>
        @else
            <div class="nh-events">
                @foreach ($upcomingEvents as $event)
                    <article class="nh-event {{ $event->is_top_event ? 'is-top' : '' }}">
                        <div class="nh-event__date">
                            @if ($event->starts_on)
                                <b>{{ $event->starts_on->format('d') }}</b>
                                <span>{{ mb_strtoupper($event->starts_on->translatedFormat('M')) }}</span>
                            @else
                                <b>—</b>
                            @endif
                        </div>
                        <div class="nh-event__body">
                            <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h3>
                            <div class="nh-tags">
                                @if ($event->is_top_event)
                                    <span class="is-gold">Top Event</span>
                                @endif
                                @if ($event->event_type)
                                    <span>{{ $event->event_type }}</span>
                                @endif
                                @if ($event->canton)
                                    <span>{{ $event->canton->code }}</span>
                                @endif
                                @if ($event->price_label)
                                    <span>{{ $event->price_label }}</span>
                                @endif
                                @if ($event->capacity_label)
                                    <span>{{ $event->capacity_label }}</span>
                                @endif
                            </div>
                        </div>
                        <a class="nh-btn nh-btn--navy nh-btn--small" href="{{ route('events.show', $event->slug) }}#anmelden">Anmelden</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- CTA --}}
    <section class="nh-cta">
        <div class="nh-cta__inner">
            <p class="nh-kicker" style="justify-content:center;display:flex;">Für Experten</p>
            <h2>Beraten und begleiten Sie Eigentümer bei der Weitergabe ihres Unternehmens? <em>Listen Sie Ihr Profil.</em></h2>
            <a class="nh-btn nh-btn--gold" href="{{ route('registration.create') }}">Als Experte anmelden ↗</a>
            <p class="nh-cta__map"><a href="{{ route('map.index') }}">Schweiz-Karte öffnen →</a></p>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    var banner = document.querySelector('[data-banner-slider]');
    if (banner) {
        var slides = Array.prototype.slice.call(banner.querySelectorAll('[data-banner-slide]'));
        var dots = Array.prototype.slice.call(banner.querySelectorAll('[data-banner-dot]'));
        var index = 0;
        var timer = null;
        var duration = 6500;

        function show(i) {
            if (!slides.length) return;
            index = (i + slides.length) % slides.length;
            slides.forEach(function (slide, n) {
                var on = n === index;
                slide.classList.toggle('is-active', on);
                slide.hidden = !on;
            });
            dots.forEach(function (dot, n) {
                var on = n === index;
                dot.classList.toggle('is-on', on);
                var fill = dot.querySelector('[data-banner-dot-fill]');
                if (fill) {
                    fill.style.animation = 'none';
                    void fill.offsetWidth;
                    if (on) fill.style.animation = '';
                }
            });
        }

        function next() { show(index + 1); }
        function prev() { show(index - 1); }

        var prevBtn = banner.querySelector('[data-banner-prev]');
        var nextBtn = banner.querySelector('[data-banner-next]');
        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); restart(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { next(); restart(); });
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                show(parseInt(dot.getAttribute('data-banner-dot'), 10) || 0);
                restart();
            });
        });

        function restart() {
            if (timer) clearInterval(timer);
            if (slides.length > 1) {
                timer = setInterval(next, duration);
                show(index);
            }
        }
        restart();
    }

    var top5 = document.querySelector('[data-top5-slider]');
    if (top5) {
        var track5 = top5.querySelector('[data-top5-track]');
        var prev5 = document.querySelector('[data-top5-prev]');
        var next5 = document.querySelector('[data-top5-next]');
        function move5(dir) {
            if (!track5) return;
            var card = track5.querySelector('.nh-top-slider__item');
            var step = card ? card.getBoundingClientRect().width + 14 : 280;
            track5.scrollBy({ left: dir * step, behavior: 'smooth' });
        }
        if (prev5) prev5.addEventListener('click', function () { move5(-1); });
        if (next5) next5.addEventListener('click', function () { move5(1); });
    }

    var root = document.querySelector('[data-carousel]');
    if (root) {
        var track = root.querySelector('[data-carousel-track]');
        var prev = root.querySelector('[data-carousel-prev]');
        var next = root.querySelector('[data-carousel-next]');
        function move(dir) {
            if (!track) return;
            track.scrollBy({ left: dir * Math.min(340, track.clientWidth * 0.85), behavior: 'smooth' });
        }
        if (prev) prev.addEventListener('click', function () { move(-1); });
        if (next) next.addEventListener('click', function () { move(1); });
    }
})();
</script>
@endpush
@endsection
