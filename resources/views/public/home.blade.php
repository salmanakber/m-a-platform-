@extends('layouts.public')

@section('title', 'nachfolge-experten.ch — M&A und Unternehmensnachfolge')
@section('meta_description', 'Das unabhängige Verzeichnis für M&A- und Nachfolge-Experten in der Schweiz — geprüft, kantonal, diskret.')
@section('body_class', 'is-home-redesign')

@php
    $heroExpert = $heroMatch?->expert;
    $heroOffice = $heroExpert?->primaryOffice();
    $periodLabel = $heroMatch?->label ?: ($nationalTop->first()?->label ?: now()->format('m.Y'));
@endphp

@section('hero')
<section class="nh-hero" aria-label="Einstieg">
    <div class="nh-hero__glow" aria-hidden="true"></div>
    <div class="nh-hero__inner">
        <div class="nh-hero__copy">
            <p class="nh-hero__eyebrow">Schweiz · M&A · Nachfolge — Verzeichnis {{ date('Y') }}</p>
            <h1 class="nh-hero__title">
                Ihr Lebenswerk.<br>
                <em>In neuen, guten Händen.</em>
            </h1>
            <p class="nh-hero__lead">Das unabhängige Verzeichnis für M&A- und Nachfolge-Experten in der Schweiz — geprüft, kantonal, diskret.</p>
            <div class="nh-hero__actions">
                <a class="nh-btn nh-btn--gold" href="#top5">Top 5 Experten ansehen ↓</a>
                <a class="nh-btn nh-btn--ghost" href="{{ route('cantons.index') }}">Kanton wählen ↗</a>
            </div>
        </div>

        <aside class="nh-match" aria-label="Top Match">
            @if ($heroExpert)
                <p class="nh-match__kicker">Top Match #{{ str_pad((string) ($heroMatch->position ?? 1), 2, '0', STR_PAD_LEFT) }} / {{ $periodLabel }}</p>
                <div class="nh-match__row">
                    <span class="nh-avatar" style="--g:{{ \App\Support\ProfessionalGroup::color($heroExpert->professional_group ?? 'ma_specialist') }}">
                        {{ mb_strtoupper(mb_substr($heroExpert->contact_person_name ?: $heroExpert->company_name, 0, 1).mb_substr($heroExpert->contact_person_last_name ?: $heroExpert->company_name, 0, 1)) }}
                    </span>
                    <div>
                        <strong>{{ trim(($heroExpert->contact_person_name ?? '').' '.($heroExpert->contact_person_last_name ?? '')) ?: $heroExpert->company_name }}</strong>
                        <span>{{ $heroExpert->company_name }}</span>
                    </div>
                </div>
                <div class="nh-match__meta">
                    @if ($heroExpert->public_rating)
                        <em>★ {{ number_format((float) $heroExpert->public_rating, 1) }} / 5.0</em>
                    @endif
                    <span>
                        @if ($heroOffice)
                            {{ $heroOffice->city }}@if($heroOffice->canton) · Kanton {{ $heroOffice->canton->code }}@endif
                        @else
                            Schweiz
                        @endif
                    </span>
                </div>
                <a class="nh-btn nh-btn--ghost nh-btn--block" href="#top5">Rangliste ansehen ↗</a>
            @else
                <p class="nh-match__kicker">Top Match</p>
                <p class="nh-match__empty">Noch kein Top Match gesetzt. Admin kann dies unter Rankings festlegen.</p>
                <a class="nh-btn nh-btn--ghost nh-btn--block" href="{{ route('directory.index') }}">Expertenverzeichnis ↗</a>
            @endif
        </aside>
    </div>

    <div class="nh-stats">
        <div><b>{{ $stats['experts'] }}</b><span>Geprüfte Experten</span></div>
        <div><b>{{ $stats['deals'] ?: '—' }}</b><span>Begleitete Transaktionen</span></div>
        <div><b>—</b><span>Transaktionsvolumen CHF</span></div>
        <div><b>{{ $stats['rating'] ? 'Ø '.$stats['rating'] : '—' }}</b><span>Kundenbewertung</span></div>
    </div>
</section>
@endsection

@section('content')
<div class="nh-page">
    {{-- Top 5 national --}}
    <section class="nh-section" id="top5">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Rangliste · {{ $periodLabel }}</p>
                <h2>Top 5 Experten der Schweiz</h2>
                <p>Die führenden M&A- und Nachfolge-Spezialisten — manuell kuratiert, zeitlich befristet.</p>
            </div>
            <a class="nh-link" href="{{ route('directory.index') }}">Alle Experten →</a>
        </div>

        @if ($nationalTop->isEmpty())
            <div class="nh-empty">Noch keine nationale Top-5-Liste. Admin setzt Rang 1–5 unter Rankings.</div>
        @else
            <div class="nh-topgrid">
                @foreach ($nationalTop as $row)
                    @php
                        $expert = $row->expert;
                        if (! $expert) continue;
                        $office = $expert->primaryOffice();
                        $initials = mb_strtoupper(mb_substr($expert->contact_person_name ?: $expert->company_name, 0, 1).mb_substr($expert->contact_person_last_name ?: '', 0, 1));
                        if (trim($initials) === '' || mb_strlen($initials) < 2) {
                            $initials = mb_strtoupper(mb_substr($expert->company_name, 0, 2));
                        }
                    @endphp
                    <article class="nh-card">
                        <span class="nh-card__rank">{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</span>
                        @if ($office?->canton)
                            <span class="nh-card__place">{{ $office->canton->code }} · {{ mb_strtoupper($office->city) }}</span>
                        @endif
                        <span class="nh-avatar" style="--g:{{ \App\Support\ProfessionalGroup::color($expert->professional_group ?? 'ma_specialist') }}">{{ $initials }}</span>
                        <h3>{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: $expert->company_name }}</h3>
                        <p class="nh-card__firm">{{ $expert->company_name }}</p>
                        <div class="nh-tags">
                            <span>{{ \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist') }}</span>
                            @if ($expert->offers_buy)<span>Kauf</span>@endif
                            @if ($expert->offers_sell)<span>Verkauf</span>@endif
                        </div>
                        <div class="nh-card__foot">
                            <span>
                                @if ($expert->public_rating)★ {{ number_format((float) $expert->public_rating, 1) }}@endif
                                @if ($expert->public_deals_count) · {{ $expert->public_deals_count }} Transaktionen@endif
                            </span>
                            <a href="{{ route('experts.show', $expert->slug) }}">Profil ↗</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Marktplätze (cards only, never map) --}}
    <section class="nh-section nh-section--soft" id="marktplaetze">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Fachgruppe</p>
                <h2>Marktplätze</h2>
                <p>Online-Marktplätze und Plattformen — als Kartenliste, nicht auf der Schweiz-Karte.</p>
            </div>
            <a class="nh-link" href="{{ route('directory.index', ['gruppe' => 'marktplatz']) }}">Alle Marktplätze →</a>
        </div>
        @if ($marktplatzTop->isEmpty())
            <div class="nh-empty">Noch keine Marktplätze gelistet.</div>
        @else
            <div class="nh-topgrid nh-topgrid--5">
                @foreach ($marktplatzTop as $row)
                    @php
                        $expert = is_object($row) && isset($row->expert) ? $row->expert : $row;
                        if (! $expert) continue;
                        $pos = $row->position ?? ($loop->iteration);
                        $office = $expert->primaryOffice();
                    @endphp
                    <article class="nh-card">
                        <span class="nh-card__rank">{{ str_pad((string) $pos, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="nh-avatar" style="--g:{{ \App\Support\ProfessionalGroup::color('marktplatz') }}">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 2)) }}</span>
                        <h3>{{ $expert->company_name }}</h3>
                        <p class="nh-card__firm">@if($office){{ $office->city }}@else Schweiz @endif</p>
                        <div class="nh-card__foot">
                            <span>Marktplatz</span>
                            <a href="{{ route('experts.show', $expert->slug) }}">Profil ↗</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Top 3 per canton --}}
    <section class="nh-section" id="kantone-top">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Regional</p>
                <h2>Top 3 Experten je Kanton</h2>
                <p>Regional verankert, national geprüft: die drei führenden Experten Ihres Kantons.</p>
            </div>
        </div>
        <div class="nh-canton">
            <aside class="nh-canton__rail">
                <p class="nh-canton__label">Kanton wählen</p>
                <div class="nh-canton__list">
                    @foreach ($cantons as $canton)
                        <a class="{{ ($selectedCanton && $selectedCanton->id === $canton->id) ? 'is-on' : '' }}"
                           href="{{ route('home', ['kanton' => $canton->code]) }}#kantone-top">
                            <b>{{ $canton->code }}</b> {{ $canton->name_de }}
                        </a>
                    @endforeach
                </div>
            </aside>
            <div class="nh-canton__main">
                <div class="nh-canton__title">
                    <h3>Top 3 in {{ $selectedCanton->name_de ?? '—' }}</h3>
                    <span>Kanton: {{ $selectedCanton->code ?? '—' }}</span>
                </div>
                @if ($cantonTop->isEmpty())
                    <div class="nh-empty">
                        Noch keine Experten in {{ $selectedCanton->name_de ?? 'diesem Kanton' }}.
                        In diesem Kanton sind derzeit keine Top-Plätze gesetzt — der Platz ist noch frei.
                        <div style="margin-top:1rem;">
                            <a class="nh-btn nh-btn--navy" href="{{ route('registration.create') }}">Als Experte anmelden ↗</a>
                        </div>
                    </div>
                @else
                    <div class="nh-canton__cards">
                        @foreach ($cantonTop as $row)
                            @php $expert = $row->expert; if (! $expert) continue; @endphp
                            <article class="nh-rankrow">
                                <span class="nh-rankrow__n">{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="nh-avatar nh-avatar--sm" style="--g:{{ \App\Support\ProfessionalGroup::color($expert->professional_group ?? 'ma_specialist') }}">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 2)) }}</span>
                                <div>
                                    <strong>{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: $expert->company_name }}</strong>
                                    <span>{{ $expert->company_name }}</span>
                                </div>
                                <div class="nh-rankrow__meta">
                                    @if ($expert->public_rating)<em>★ {{ number_format((float) $expert->public_rating, 1) }}</em>@endif
                                    @if ($expert->public_deals_count)<span>{{ $expert->public_deals_count }} Deals</span>@endif
                                </div>
                                <a href="{{ route('experts.show', $expert->slug) }}">Profil →</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Fachbeiträge Top 3 + carousel --}}
    <section class="nh-section nh-section--dark" id="fachbeitraege">
        <div class="nh-section__head nh-section__head--light">
            <div>
                <p class="nh-kicker">Wissen</p>
                <h2>Fachbeiträge zur Nachfolge</h2>
                <p>Top 3 kuratiert — weitere Beiträge im Karussell. Ohne Kantons-Filter.</p>
            </div>
            <a class="nh-link nh-link--light" href="{{ route('blog.index') }}">Alle Beiträge →</a>
        </div>

        @if ($articleTop->isNotEmpty())
            <div class="nh-articles-top">
                @foreach ($articleTop as $row)
                    @php $article = $row->article; if (! $article) continue; @endphp
                    <a class="nh-article-card" href="{{ route('blog.show', $article->slug) }}">
                        <span>{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $article->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($article->excerpt ?? $article->body ?? '')), 110) }}</p>
                        <em>Lesen →</em>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($articleCarousel->isNotEmpty())
            <div class="nh-carousel" data-carousel>
                <button type="button" class="nh-carousel__btn" data-carousel-prev aria-label="Zurück">‹</button>
                <div class="nh-carousel__track" data-carousel-track>
                    @foreach ($articleCarousel as $article)
                        <a class="nh-carousel__item" href="{{ route('blog.show', $article->slug) }}">
                            <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <strong>{{ $article->title }}</strong>
                            <em>Lesen →</em>
                        </a>
                    @endforeach
                </div>
                <button type="button" class="nh-carousel__btn" data-carousel-next aria-label="Weiter">›</button>
            </div>
        @elseif ($articleTop->isEmpty())
            <div class="nh-empty nh-empty--dark">Noch keine Fachbeiträge veröffentlicht.</div>
        @endif
    </section>

    {{-- Events --}}
    <section class="nh-section" id="events">
        <div class="nh-section__head">
            <div>
                <p class="nh-kicker">Veranstaltungen</p>
                <h2>Events &amp; Webinare</h2>
                <p>Vergangene Termine verschwinden von der Startseite, bleiben aber im Expertenprofil.</p>
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
                                @if ($event->is_top_event)<span class="is-gold">Top Event</span>@endif
                                @if ($event->event_type)<span>{{ $event->event_type }}</span>@endif
                                @if ($event->canton)<span>{{ $event->canton->code }}</span>@endif
                                @if ($event->price_label)<span>{{ $event->price_label }}</span>@endif
                                @if ($event->capacity_label)<span>{{ $event->capacity_label }}</span>@endif
                            </div>
                        </div>
                        <a class="nh-btn nh-btn--navy nh-btn--small" href="{{ route('events.show', $event->slug) }}">Anmelden</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Expert CTA --}}
    <section class="nh-cta">
        <div class="nh-cta__inner">
            <h2>Beraten und begleiten Sie Eigentümer bei der Weitergabe der Unternehmung? <em>Listen Sie Ihr Profil.</em></h2>
            <a class="nh-btn nh-btn--gold" href="{{ route('registration.create') }}">Profil einreichen ↗</a>
            <p class="nh-cta__map"><a href="{{ route('map.index') }}">Schweiz-Karte öffnen (Unterseite) →</a></p>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-carousel]');
    if (!root) return;
    var track = root.querySelector('[data-carousel-track]');
    var prev = root.querySelector('[data-carousel-prev]');
    var next = root.querySelector('[data-carousel-next]');
    function scrollBy(dir) {
        track.scrollBy({ left: dir * Math.min(320, track.clientWidth * 0.8), behavior: 'smooth' });
    }
    if (prev) prev.addEventListener('click', function () { scrollBy(-1); });
    if (next) next.addEventListener('click', function () { scrollBy(1); });
})();
</script>
@endpush
@endsection
