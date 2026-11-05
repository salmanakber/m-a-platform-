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
                @include('public.partials.nh-open-slot-card', [
                    'rank' => '06',
                    'remark' => $contactRemark,
                    'mail' => $contactMail,
                    'subject' => 'Top-Position Schweiz · nachfolge-experten.ch',
                ])
            </div>
        @endif
    </section>

    @php
        $openMail = $footerContactEmail ?? 'kontakt@nachfolge-experten.ch';
        $openRemark = $nationalContactRemark
            ?? 'Platz frei — melden Sie sich bei uns für eine Top-Position auf nachfolge-experten.ch.';
        $fachStrips = [
            [
                'id' => 'juristen',
                'kicker' => 'Fachgruppe',
                'title' => 'Juristen',
                'lead' => 'Rechtliche Begleitung für Unternehmensnachfolge, Kauf und Verkauf — geprüfte Juristen im Verzeichnis. Nummeriert nebeneinander; Platz 6 bleibt offen.',
                'cards' => $juristCards ?? collect(),
                'tag' => 'Jurist',
                'link' => route('directory.index', ['gruppe' => 'jurist']),
                'linkLabel' => 'Alle Juristen →',
                'subject' => 'Top-Position Juristen · nachfolge-experten.ch',
                'band' => false,
            ],
            [
                'id' => 'treuhand',
                'kicker' => 'Fachgruppe',
                'title' => 'Treuhand',
                'lead' => 'Treuhand- und Revisionspartner für Unternehmensnachfolge, Bewertung und Transaktionsbegleitung. Nummeriert nebeneinander; Platz 6 bleibt offen.',
                'cards' => $treuhandCards ?? collect(),
                'tag' => 'Treuhand',
                'link' => route('directory.index', ['gruppe' => 'treuhand']),
                'linkLabel' => 'Alle Treuhand →',
                'subject' => 'Top-Position Treuhand · nachfolge-experten.ch',
                'band' => true,
            ],
            [
                'id' => 'ad-interim',
                'kicker' => 'Fachgruppe',
                'title' => 'Ad Interim',
                'lead' => 'Ad-Interim-Kandidaten für operative Nachfolge und Übergangsmandate. Leere Plätze werden als offene Kontaktkarte gezeigt.',
                'cards' => $adInterimCards ?? collect(),
                'tag' => 'Ad Interim',
                'link' => route('directory.index', ['gruppe' => 'ad_interim']),
                'linkLabel' => 'Alle Ad Interim →',
                'subject' => 'Top-Position Ad Interim · nachfolge-experten.ch',
                'band' => false,
                'fillEmpty' => true,
            ],
            [
                'id' => 'marktplaetze',
                'kicker' => 'Fachgruppe',
                'title' => 'Marktplätze',
                'lead' => 'Digitale Plattformen und Marktplätze für Unternehmensnachfolge und M&A. Nummeriert nebeneinander; Platz 6 bleibt offen.',
                'cards' => $marktplatzCards ?? collect(),
                'tag' => 'Marktplatz',
                'link' => route('directory.index', ['gruppe' => 'marktplatz']),
                'linkLabel' => 'Alle Marktplätze →',
                'subject' => 'Top-Position Marktplätze · nachfolge-experten.ch',
                'band' => true,
            ],
        ];
    @endphp

    @foreach ($fachStrips as $strip)
        @php
            $stripCards = collect($strip['cards'])->take(5)->values();
            $fillEmpty = ! empty($strip['fillEmpty']);
            $padTo = ($fillEmpty || $stripCards->isEmpty()) ? 5 : $stripCards->count();
        @endphp
        @if (! empty($strip['band']))
            <section class="nh-band nh-band--soft" id="{{ $strip['id'] }}">
                <div class="nh-section">
        @else
            <section class="nh-section" id="{{ $strip['id'] }}">
        @endif
                <div class="nh-section__head">
                    <div>
                        <p class="nh-kicker">{{ $strip['kicker'] }}</p>
                        <h2>{{ $strip['title'] }}</h2>
                        <p>{{ $strip['lead'] }}</p>
                    </div>
                    <a class="nh-link" href="{{ $strip['link'] }}">{{ $strip['linkLabel'] }}</a>
                </div>
                <div class="nh-topgrid nh-topgrid--six">
                    @foreach ($stripCards as $card)
                        @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => $strip['tag']])
                    @endforeach
                    @for ($i = $stripCards->count() + 1; $i <= $padTo; $i++)
                        @include('public.partials.nh-open-slot-card', [
                            'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                            'remark' => $openRemark,
                            'mail' => $openMail,
                            'subject' => $strip['subject'],
                            'title' => 'Platz '.$i,
                        ])
                    @endfor
                    @include('public.partials.nh-open-slot-card', [
                        'rank' => '06',
                        'remark' => $openRemark,
                        'mail' => $openMail,
                        'subject' => $strip['subject'],
                    ])
                </div>
        @if (! empty($strip['band']))
                </div>
            </section>
        @else
            </section>
        @endif
    @endforeach

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

                @php $cantonShow = ($cantonCards ?? collect())->take(5)->values(); @endphp
                <div class="nh-canton__cards">
                    @forelse ($cantonShow as $card)
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
                    @empty
                        <div class="nh-empty nh-empty--panel" style="margin-bottom:.5rem;">
                            <strong>Noch keine Top-5-Ränge in {{ $selectedCanton->name_de ?? 'diesem Kanton' }}.</strong>
                            <p>
                                @if (($cs['total'] ?? 0) > 0)
                                    In diesem Kanton sind {{ $cs['total'] }} geprüfte Experten aktiv — offene Plätze können Sie belegen.
                                @else
                                    Offene Plätze — melden Sie sich für eine Top-Position in diesem Kanton.
                                @endif
                            </p>
                        </div>
                        @for ($i = 1; $i <= 5; $i++)
                            @include('public.partials.nh-open-slot-card', [
                                'variant' => 'row',
                                'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                                'title' => 'Platz '.$i,
                                'remark' => 'Top-Position Kanton '.$selectedCanton->code.' — Platz frei.',
                                'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                                'subject' => 'Top 5 Kanton '.($selectedCanton->code ?? '').' · nachfolge-experten.ch',
                            ])
                        @endfor
                    @endforelse
                    @include('public.partials.nh-open-slot-card', [
                        'variant' => 'row',
                        'rank' => '06',
                        'remark' => 'Platz 6 offen für Ihre Anfrage in '.$selectedCanton->name_de.'.',
                        'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                        'subject' => 'Top 5 Kanton '.($selectedCanton->code ?? '').' · nachfolge-experten.ch',
                    ])
                </div>

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

    {{-- Fachbeiträge — Top je Fachbereich + offener Platz; Themenliste darunter (nicht gleiche Ebene) --}}
    <section class="nh-band nh-band--dark" id="fachbeitraege">
        <div class="nh-section nh-section--flush">
            <div class="nh-section__head nh-section__head--light">
                <div>
                    <p class="nh-kicker">Wissen</p>
                    <h2>Fachbeiträge zur Nachfolge</h2>
                    <p>Top-Beiträge je Fachbereich — nummeriert. Der letzte Platz bleibt offen für Ihre Anfrage.</p>
                </div>
                <a class="nh-link nh-link--light" href="{{ route('blog.index') }}">Alle Beiträge →</a>
            </div>

            @if (($fachGroupTops ?? collect())->isNotEmpty())
                <div class="nh-fach-groups">
                    @foreach ($fachGroupTops as $group)
                        @php $gCards = collect($group['cards'])->take(3)->values(); @endphp
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
                                @foreach ($gCards as $article)
                                    <a class="nh-article-card" href="{{ $article['url'] }}">
                                        <span class="nh-article-card__n">{{ $article['n'] }}</span>
                                        <h3>{{ $article['title'] }}</h3>
                                        <p>{{ $article['excerpt'] }}</p>
                                        <em>Lesen →</em>
                                    </a>
                                @endforeach
                                @for ($i = $gCards->count() + 1; $i <= 3; $i++)
                                    @include('public.partials.nh-open-slot-card', [
                                        'variant' => 'article',
                                        'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                                        'title' => 'Platz '.$i.' offen',
                                        'remark' => 'Top-Beitrag «'.$group['category']->name_de.'» — Platz frei.',
                                        'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                                        'subject' => 'Fachbeitrag Top · '.$group['category']->name_de,
                                    ])
                                @endfor
                                @include('public.partials.nh-open-slot-card', [
                                    'variant' => 'article',
                                    'rank' => '04',
                                    'title' => 'Ihr Platz',
                                    'remark' => 'Platz offen für Ihren Fachbeitrag in «'.$group['category']->name_de.'».',
                                    'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                                    'subject' => 'Fachbeitrag Top · '.$group['category']->name_de,
                                ])
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
                    @include('public.partials.nh-open-slot-card', [
                        'variant' => 'article',
                        'rank' => '04',
                        'remark' => 'Platz offen für Ihren Fachbeitrag.',
                        'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                        'subject' => 'Fachbeitrag Top · nachfolge-experten.ch',
                    ])
                </div>
            @endif

            {{-- Weitere Artikel (Carousel) — unter den Fachgruppen, nicht gleiche Ebene --}}
            @if ($articleCarouselCards->isNotEmpty())
                <div class="nh-fach-more">
                    <p class="nh-fach-more__label">Weitere aktuelle Beiträge</p>
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
                </div>
            @elseif (($fachGroupTops ?? collect())->isEmpty() && $articleTopCards->isEmpty())
                <div class="nh-articles-top nh-articles-top--compact">
                    @for ($i = 1; $i <= 3; $i++)
                        @include('public.partials.nh-open-slot-card', [
                            'variant' => 'article',
                            'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                            'title' => 'Platz '.$i.' offen',
                            'remark' => 'Fachbeitrag-Platz frei — melden Sie sich bei uns.',
                            'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                            'subject' => 'Fachbeitrag · nachfolge-experten.ch',
                        ])
                    @endfor
                    @include('public.partials.nh-open-slot-card', [
                        'variant' => 'article',
                        'rank' => '04',
                        'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                        'subject' => 'Fachbeitrag · nachfolge-experten.ch',
                    ])
                </div>
            @endif

            {{-- Themenliste unter Fachbeiträge (verschachtelt, nicht gleiche Ebene wie H2) --}}
            @if (($navArticleCategories ?? collect())->isNotEmpty())
                <nav class="nh-fach-topics" aria-label="Fachbereiche">
                    <p class="nh-fach-topics__label">Themen unter Fachbeiträge</p>
                    <ul class="nh-fach-topics__list">
                        @foreach ($navArticleCategories as $cat)
                            <li>
                                <a href="{{ route('blog.index', ['gruppe' => $cat->slug]) }}">{{ $cat->name_de }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            {{-- Separater Bereich: Top 5 Videos + offener Platz --}}
            <div class="nh-fach-videos" id="videos">
                <div class="nh-section__head nh-section__head--light" style="margin-top:2rem;">
                    <div>
                        <p class="nh-kicker">Videos</p>
                        <h2 style="color:#fff;">Top 5 Videos</h2>
                        <p>Separater Bereich unter Fachbeiträge — nummeriert. Platz 6 bleibt offen. Erstes Video gratis, weitere kostenpflichtig.</p>
                    </div>
                </div>
                @php $videoCards = ($homeVideos ?? collect())->take(5)->values(); @endphp
                <div class="nh-topgrid nh-topgrid--six nh-topgrid--videos">
                    @foreach ($videoCards as $video)
                        <article class="nh-card nh-card--video">
                            <span class="nh-card__rank" aria-hidden="true">{{ $video['n'] }}</span>
                            <span class="nh-card__place">VIDEO</span>
                            @if (!empty($video['embed']))
                                <div class="nh-video-frame">
                                    <iframe src="{{ $video['embed'] }}" title="{{ $video['title'] }}" loading="lazy" allowfullscreen></iframe>
                                </div>
                            @else
                                <a class="nh-video-link" href="{{ $video['url'] }}" target="_blank" rel="noopener">Video öffnen ↗</a>
                            @endif
                            <h3>{{ $video['title'] }}</h3>
                            <p class="nh-card__firm">{{ $video['company'] }}</p>
                            <div class="nh-card__foot">
                                <span>Expertenvideo</span>
                                @if (!empty($video['profile_url']))
                                    <a href="{{ $video['profile_url'] }}">Profil ↗</a>
                                @else
                                    <a href="{{ $video['url'] }}" target="_blank" rel="noopener">Link ↗</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                    @for ($i = $videoCards->count() + 1; $i <= 5; $i++)
                        @include('public.partials.nh-open-slot-card', [
                            'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                            'title' => 'Video-Platz '.$i,
                            'subtitle' => 'Noch frei',
                            'remark' => 'Top-Video-Platz offen — 1. Video gratis, weitere gegen Gebühr.',
                            'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                            'subject' => 'Top 5 Video · nachfolge-experten.ch',
                        ])
                    @endfor
                    @include('public.partials.nh-open-slot-card', [
                        'rank' => '06',
                        'title' => 'Ihr Video-Platz',
                        'remark' => 'Platz 6 offen für Ihre Anfrage / weiteres Video.',
                        'mail' => $openMail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch'),
                        'subject' => 'Top 5 Video · nachfolge-experten.ch',
                    ])
                </div>
            </div>
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
