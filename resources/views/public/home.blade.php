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

            <form class="nh-finder" method="get" action="{{ route('directory.index') }}" role="search" aria-label="Experten finden" data-hero-finder>
                <div class="nh-finder__head">
                    <span class="nh-finder__eyebrow">Expertenfinder</span>
                    <p class="nh-finder__title">Ich möchte verkaufen oder kaufen — wer passt zu mir?</p>
                </div>
                <div class="nh-finder__row">
                    <div class="nh-finder__field" data-fancy-select>
                        <span class="nh-finder__label">Kanton</span>
                        <input type="hidden" name="kanton" value="{{ $selectedCanton->code ?? '' }}" data-fancy-value required>
                        <button type="button" class="nh-finder__trigger" data-fancy-trigger aria-haspopup="listbox" aria-expanded="false">
                            <span data-fancy-label>{{ ($selectedCanton->code ?? '') !== '' ? ($selectedCanton->code.' — '.$selectedCanton->name_de) : 'Kanton wählen' }}</span>
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <ul class="nh-finder__menu" data-fancy-menu role="listbox" hidden>
                            <li role="option" data-value="" data-label="Kanton wählen">Kanton wählen</li>
                            @foreach ($cantons as $canton)
                                <li role="option" data-value="{{ $canton->code }}" data-label="{{ $canton->code }} — {{ $canton->name_de }}" @if(($selectedCanton->code ?? '') === $canton->code) aria-selected="true" @endif>
                                    <b>{{ $canton->code }}</b>
                                    <span>{{ $canton->name_de }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="nh-finder__field" data-fancy-select>
                        <span class="nh-finder__label">Ziel</span>
                        <input type="hidden" name="ziel" value="" data-fancy-value required>
                        <button type="button" class="nh-finder__trigger" data-fancy-trigger aria-haspopup="listbox" aria-expanded="false">
                            <span data-fancy-label>Ziel wählen</span>
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <ul class="nh-finder__menu" data-fancy-menu role="listbox" hidden>
                            <li role="option" data-value="" data-label="Ziel wählen">Ziel wählen</li>
                            @foreach ($searchObjectives as $value => $label)
                                <li role="option" data-value="{{ $value }}" data-label="{{ $label }}">{{ $label }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="submit" class="nh-btn nh-btn--gold nh-finder__submit">
                        <span>Passende finden</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M10 6l-1.4 1.4L12.2 12l-3.6 4.6L10 18l6-6z"/></svg>
                    </button>
                </div>
                <p class="nh-finder__hint">Kanton und Ziel wählen — wir zeigen geprüfte Experten im Verzeichnis.</p>
            </form>
        </div>

        <aside class="nh-match" aria-label="Empfohlene Experten" data-banner-slider>
            @if (($bannerCards ?? collect())->isNotEmpty())
                <div class="nh-match__chrome">
                    <div class="nh-match__badge">
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M12 2l2.4 4.9L20 8.2l-3.6 3.5.9 5.2L12 14.8 6.7 16.9l.9-5.2L4 8.2l5.6-1.3L12 2z"/></svg>
                        Empfohlen
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
                                    @if ($card['rating'])
                                        <em>★ {{ $card['rating'] }} / 5.0</em>
                                    @endif
                                    <span>{{ $card['place_line'] }}</span>
                                </div>
                                <a class="nh-btn nh-btn--ghost nh-btn--block" href="{{ $card['url'] }}">Profil ansehen ↗</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="nh-match__badge">Empfohlen</div>
                <p class="nh-match__empty">Nutzen Sie die Suche links — oder entdecken Sie alle geprüften Profile im Verzeichnis.</p>
                <a class="nh-btn nh-btn--ghost nh-btn--block" href="{{ route('directory.index') }}">Expertenverzeichnis ↗</a>
            @endif
        </aside>
    </div>

    <div class="nh-stats" data-banner-stats>
        <div><b>{{ number_format($stats['experts']) }}</b><span>Geprüfte Experten</span></div>
        @if (!empty($stats['cantons_covered']))
            <div>
                <b>{{ number_format($stats['cantons_covered']) }}</b>
                <span>Kantone abgedeckt</span>
            </div>
        @endif
        @if (!empty($stats['deals']))
            <div><b>{{ number_format($stats['deals']) }}</b><span>Begleitete Transaktionen</span></div>
        @endif
        @if (!empty($stats['rating']))
            <div><b>Ø {{ number_format($stats['rating'], 1) }}</b><span>Kundenbewertung</span></div>
        @endif
        @if (($bannerCards ?? collect())->count() > 1)
            <div class="nh-match__dots nh-match__dots--stats" data-banner-dots role="tablist" aria-label="Empfohlene Experten">
                @foreach ($bannerCards as $index => $card)
                    <button type="button" class="nh-match__dot {{ $index === 0 ? 'is-on' : '' }}" data-banner-dot="{{ $index }}" aria-label="Slide {{ $index + 1 }}">
                        <span class="nh-match__dot-fill" data-banner-dot-fill></span>
                    </button>
                @endforeach
            </div>
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

    {{-- 1) Berater = Top 5 Schweiz (neben­einander, 2 Reihen à 3) + Kontaktkarte --}}
    <section class="nh-section" id="berater">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Top Schweiz · {{ $periodLabel }}</p>
                <h2>Berater</h2>
                <p>Die nationale Top-Auswahl — nummeriert, nebeneinander. Platz 6 bleibt offen für Ihre Anfrage.</p>
            </div>
            <div class="nh-section__tools">
                <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'ma_specialist']) }}">Alle Berater →</a>
            </div>
        </div>

        @php
            // Top 5 Schweiz (Admin) first — fixes mismatch with «Top Fachgruppe».
            $beraterShow = ($nationalCards ?? collect())->isNotEmpty()
                ? $nationalCards->take(5)->values()
                : (($beraterCards ?? collect())->take(5)->values());
            $contactRemark = $nationalContactRemark
                ?? 'Platz frei — melden Sie sich bei uns für eine Top-Position auf nachfolge-experten.ch.';
            $contactMail = $footerContactEmail ?? 'kontakt@nachfolge-experten.ch';
        @endphp
        @if ($beraterShow->isEmpty())
            <div class="nh-empty">
                <strong>Noch keine Berater veröffentlicht.</strong>
                <p>Pflegen Sie «Top 5 Schweiz» unter Rankings — oder geprüfte Profile erscheinen im Verzeichnis.</p>
            </div>
        @else
            <div class="nh-topgrid nh-topgrid--six" id="top5">
                @foreach ($beraterShow as $card)
                    @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => 'Berater'])
                @endforeach
                <article class="nh-card nh-card--contact">
                    <span class="nh-card__rank" aria-hidden="true">06</span>
                    <span class="nh-card__place">OFFEN</span>
                    <span class="nh-avatar nh-avatar--contact" aria-hidden="true">?</span>
                    <h3>Ihr Platz</h3>
                    <p class="nh-card__firm">Kontakt &amp; Bemerkung</p>
                    <p class="nh-card__remark">{{ $contactRemark }}</p>
                    <div class="nh-card__foot">
                        <span>Direkt melden</span>
                        <a href="mailto:{{ $contactMail }}?subject={{ rawurlencode('Top-Position nachfolge-experten.ch') }}">Kontakt ↗</a>
                    </div>
                </article>
            </div>
        @endif
    </section>

    {{-- 2) Juristen --}}
    <section class="nh-section" id="juristen">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Fachgruppe</p>
                <h2>Juristen</h2>
                <p>Rechtliche Begleitung für Unternehmensnachfolge, Kauf und Verkauf — geprüfte Juristen im Verzeichnis.</p>
            </div>
            <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'jurist']) }}">Alle Juristen →</a>
        </div>
        @if (($juristCards ?? collect())->isEmpty())
            <div class="nh-empty">Derzeit sind noch keine Juristen veröffentlicht. Im Verzeichnis können Sie die Fachgruppe «Juristen» filtern.</div>
        @else
            <div class="nh-topgrid">
                @foreach ($juristCards as $card)
                    @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => 'Jurist'])
                @endforeach
            </div>
        @endif
    </section>

    {{-- 3) Treuhand (like Juristen) --}}
    <section class="nh-band nh-band--soft" id="treuhand">
        <div class="nh-section">
            <div class="nh-section__head">
                <div>
                    <p class="nh-kicker">Fachgruppe</p>
                    <h2>Treuhand</h2>
                    <p>Treuhand- und Revisionspartner für Unternehmensnachfolge, Bewertung und Transaktionsbegleitung in der Schweiz.</p>
                </div>
                <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'treuhand']) }}">Alle Treuhand →</a>
            </div>
            @if (($treuhandCards ?? collect())->isEmpty())
                <div class="nh-empty">Derzeit sind noch keine Treuhand-Profile veröffentlicht. Im Verzeichnis können Sie die Fachgruppe «Treuhand» filtern.</div>
            @else
                <div class="nh-topgrid">
                    @foreach ($treuhandCards as $card)
                        @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => 'Treuhand'])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 4) Ad Interim (under Treuhand) --}}
    <section class="nh-section" id="ad-interim">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Fachgruppe</p>
                <h2>Ad Interim</h2>
                <p>Ad-Interim-Kandidaten für operative Nachfolge, Übergangsmandate und temporäre Führungsverantwortung.</p>
            </div>
            <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'ad_interim']) }}">Alle Ad Interim →</a>
        </div>
        @if (($adInterimCards ?? collect())->isEmpty())
            <div class="nh-empty">Derzeit sind noch keine Ad-Interim-Profile veröffentlicht. Im Verzeichnis können Sie die Fachgruppe «Ad Interim» filtern.</div>
        @else
            <div class="nh-topgrid">
                @foreach ($adInterimCards as $card)
                    @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => 'Ad Interim'])
                @endforeach
            </div>
        @endif
    </section>

    {{-- 5) Marktplätze --}}
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

    {{-- Top 5 Kanton --}}
    <section class="nh-section" id="kantone-top">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Regional</p>
                <h2>Top 5 Experten je Kanton</h2>
                <p>Wählen Sie Ihren Kanton — sehen Sie aktive Experten vor Ort und die kuratierte Top 5.</p>
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
                    </a>
                @endforeach
            </div>
        @endif

        @php $cs = $selectedCantonStats ?? ['total' => 0, 'ma' => 0, 'jurist' => 0, 'other' => 0]; @endphp
        <div class="nh-canton nh-canton--single">
            <div class="nh-canton__main">
                <div class="nh-canton__title">
                    <h3>Top 5 in {{ $selectedCanton->name_de ?? '—' }}</h3>
                    <span>Kanton {{ $selectedCanton->code ?? '—' }} · vorausgewählt</span>
                </div>

                @if ($cantonCards->isEmpty())
                    <div class="nh-empty nh-empty--panel">
                        <strong>Noch keine Top-5-Ränge in {{ $selectedCanton->name_de ?? 'diesem Kanton' }}.</strong>
                        <p>
                            @if (($cs['total'] ?? 0) > 0)
                                In diesem Kanton sind {{ $cs['total'] }} geprüfte Experten aktiv — eine kuratierte Top-5-Liste folgt.
                            @else
                                In diesem Kanton sind derzeit keine geprüften Experten gelistet.
                            @endif
                        </p>
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

                <a class="nh-canton__all" href="{{ route('directory.index', ['kanton' => $selectedCanton->code ?? '']) }}">
                    <span class="nh-canton__all-copy">
                        <strong>Alle Experten in {{ $selectedCanton->name_de ?? 'diesem Kanton' }}</strong>
                        <em>{{ (int) ($cs['total'] ?? 0) }} geprüfte Profile im Verzeichnis</em>
                    </span>
                    <span class="nh-canton__all-count">{{ (int) ($cs['total'] ?? 0) }}</span>
                    <span class="nh-canton__all-cta">Alle ansehen →</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Fachbeiträge — Top 3 je Fachbereich --}}
    <section class="nh-band nh-band--dark" id="fachbeitraege">
        <div class="nh-section nh-section--flush">
            <div class="nh-section__head nh-section__head--light">
                <div>
                    <p class="nh-kicker">Wissen</p>
                    <h2>Fachbeiträge zur Nachfolge</h2>
                    <p>Top 3 Beiträge in jedem Fachbereich — plus weitere aktuelle Artikel.</p>
                </div>
                <a class="nh-link nh-link--light" href="{{ route('blog.index') }}">Alle Beiträge →</a>
            </div>

            @if (($fachGroupTops ?? collect())->isNotEmpty())
                <div class="nh-fach-groups">
                    @foreach ($fachGroupTops as $group)
                        <article class="nh-fach-group">
                            <header class="nh-fach-group__head">
                                <div>
                                    <h3>{{ $group['category']->name_de }}</h3>
                                    @if ($group['category']->description_de)
                                        <p>{{ \Illuminate\Support\Str::limit($group['category']->description_de, 110) }}</p>
                                    @endif
                                </div>
                                <a class="nh-link nh-link--light" href="{{ $group['url'] }}">Gruppe →</a>
                            </header>
                            <div class="nh-articles-top nh-articles-top--compact">
                                @foreach ($group['cards'] as $article)
                                    <a class="nh-article-card" href="{{ $article['url'] }}">
                                        <span class="nh-article-card__n">{{ $article['n'] }}</span>
                                        <h3>{{ $article['title'] }}</h3>
                                        <p>{{ $article['excerpt'] }}</p>
                                        <em>Lesen →</em>
                                    </a>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @elseif ($articleTopCards->isNotEmpty())
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
            @elseif (($fachGroupTops ?? collect())->isEmpty() && $articleTopCards->isEmpty())
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
        var dots = Array.prototype.slice.call(document.querySelectorAll('[data-banner-dot]'));
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

    // Custom hero selects
    document.querySelectorAll('[data-fancy-select]').forEach(function (wrap) {
        var trigger = wrap.querySelector('[data-fancy-trigger]');
        var menu = wrap.querySelector('[data-fancy-menu]');
        var label = wrap.querySelector('[data-fancy-label]');
        var input = wrap.querySelector('[data-fancy-value]');
        if (!trigger || !menu || !label || !input) return;

        function close() {
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            wrap.classList.remove('is-open');
        }
        function open() {
            document.querySelectorAll('[data-fancy-select].is-open').forEach(function (other) {
                if (other !== wrap) {
                    var m = other.querySelector('[data-fancy-menu]');
                    var t = other.querySelector('[data-fancy-trigger]');
                    if (m) m.hidden = true;
                    if (t) t.setAttribute('aria-expanded', 'false');
                    other.classList.remove('is-open');
                }
            });
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            wrap.classList.add('is-open');
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (menu.hidden) open(); else close();
        });

        menu.querySelectorAll('[role="option"]').forEach(function (opt) {
            opt.addEventListener('click', function () {
                var value = opt.getAttribute('data-value') || '';
                var text = opt.getAttribute('data-label') || opt.textContent.trim();
                input.value = value;
                label.textContent = text;
                menu.querySelectorAll('[role="option"]').forEach(function (o) {
                    o.removeAttribute('aria-selected');
                });
                if (value !== '') opt.setAttribute('aria-selected', 'true');
                close();
            });
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-fancy-select]')) return;
        document.querySelectorAll('[data-fancy-select].is-open').forEach(function (wrap) {
            var m = wrap.querySelector('[data-fancy-menu]');
            var t = wrap.querySelector('[data-fancy-trigger]');
            if (m) m.hidden = true;
            if (t) t.setAttribute('aria-expanded', 'false');
            wrap.classList.remove('is-open');
        });
    });

    var finder = document.querySelector('[data-hero-finder]');
    if (finder) {
        finder.addEventListener('submit', function (e) {
            var kanton = finder.querySelector('[name="kanton"]');
            var ziel = finder.querySelector('[name="ziel"]');
            if (!kanton || !ziel || !kanton.value || !ziel.value) {
                e.preventDefault();
                finder.classList.add('is-invalid');
                return;
            }
            finder.classList.remove('is-invalid');
        });
    }
})();
</script>
@endpush
@endsection
