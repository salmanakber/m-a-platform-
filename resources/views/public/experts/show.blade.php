@extends('layouts.public')

@section('title', ($expert->isAdInterim() ? $expert->publicDisplayName() : $expert->company_name).' — nachfolge-experten.ch')
@section('meta_description', ($expert->isAdInterim() ? $expert->interimListingLine() : $expert->company_name).' auf nachfolge-experten.ch')
@section('flush')
@endsection

@section('content')
@php
    $offices = $offices ?? collect([$office])->filter();
    $isMarketplace = ($expert->professional_group ?? '') === \App\Support\ProfessionalGroup::MARKTPLATZ;
    $isInterim = $expert->isAdInterim();
    $headlineName = $isInterim ? $expert->publicDisplayName() : $expert->company_name;
    $website = trim((string) ($expert->website ?? ''));
    if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
        $website = 'https://'.$website;
    }
    $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : null;
@endphp

<div class="page-main--flush">
    <div class="container" style="padding-top:1rem;">
        @include('partials.flash')
    </div>
    <section class="xp-stage">
        <div class="container">
            <a class="xp-stage__crumb" href="{{ $isMarketplace ? route('directory.index', ['gruppe' => 'marktplatz']) : ($isInterim ? route('directory.index', ['gruppe' => 'ad_interim']) : route('directory.index')) }}">
                ← {{ $isMarketplace ? 'Marktplätze' : ($isInterim ? 'Ad Interim' : 'Expertenverzeichnis') }}
            </a>
            <div class="xp-stage__grid reveal-on-scroll">
                <div class="xp-mark logo-pos--{{ $expert->logo_position ?? 'left' }}" aria-hidden="true">
                    @if ($isInterim && $expert->contact_photo_path)
                        <img src="{{ asset('storage/'.$expert->contact_photo_path) }}" alt="">
                    @elseif ($expert->logo_path)
                        <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                    @else
                        <span class="xp-mark__letter">{{ mb_strtoupper(mb_substr($headlineName, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="xp-stage__meta">
                    <p class="xp-stage__eyebrow">{{ \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist') }} · Schweiz</p>
                    <h1 class="xp-stage__name">{{ $headlineName }}</h1>
                    @if ($isInterim)
                        <p class="xp-stage__place">{{ $expert->interimListingLine() }}</p>
                    @elseif ($isMarketplace)
                        <p class="xp-stage__place">Schweizweit · Digitaler Marktplatz</p>
                        @if ($websiteHost)
                            <p class="xp-stage__web">
                                <a href="{{ $website }}" target="_blank" rel="noopener">{{ $websiteHost }} ↗</a>
                            </p>
                        @endif
                    @elseif ($office)
                        <p class="xp-stage__place">
                            {{ $office->address_line }}<br>
                            {{ $office->postal_code }} {{ $office->city }}
                            @if ($office->canton)
                                · {{ $office->canton->name_de }}
                            @endif
                        </p>
                    @endif
                    <div class="xp-stage__chips">
                        <span class="xp-chip">{{ \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist') }}</span>
                        @if ($isInterim && filled($expert->equity_capital))
                            <span class="xp-chip">{{ $expert->equity_capital }} Eigenkapital</span>
                        @endif
                        @if ($isMarketplace)
                            <span class="xp-chip">CH</span>
                        @else
                            @if ($expert->offers_buy)<span class="xp-chip">Kauf</span>@endif
                            @if ($expert->offers_sell)<span class="xp-chip">Verkauf</span>@endif
                            @if ($office && $office->canton)<span class="xp-chip">{{ $office->canton->code }}</span>@endif
                        @endif
                        <span class="xp-chip">Geprüft</span>
                    </div>
                </div>
                @if ($expert->contact_photo_path || $expert->video_url)
                    <div class="xp-stage__person">
                        @if ($expert->contact_photo_path)
                            <img class="xp-stage__photo" src="{{ asset('storage/'.$expert->contact_photo_path) }}" alt="{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: $expert->company_name }}">
                        @endif
                        @if ($expert->video_url)
                            <a class="xp-stage__video" href="{{ $expert->video_url }}" target="_blank" rel="noopener">Video ansehen ↗</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="xp-body">
        <div class="container">
            @include('partials.flash')
            <div class="xp-layout {{ $isMarketplace ? 'xp-layout--single' : '' }}">
                <div>
                    @unless ($isMarketplace)
                        <div class="xp-trust reveal-on-scroll">
                            <div class="xp-trust__item">
                                <strong>Diskret</strong>
                                <span>Keine öffentlichen Kontaktdaten</span>
                            </div>
                            <div class="xp-trust__item">
                                <strong>Direkt</strong>
                                <span>Weiterleitung an die Firma</span>
                            </div>
                            <div class="xp-trust__item">
                                <strong>1–2 Tage</strong>
                                <span>Übliche Rückmeldung</span>
                            </div>
                        </div>
                    @endunless

                    @if ($expert->contact_photo_path || $expert->video_url)
                        @php
                            $contactName = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));
                            $videoUrl = trim((string) ($expert->video_url ?? ''));
                            $embedUrl = null;
                            if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $videoUrl, $m)) {
                                $embedUrl = 'https://www.youtube.com/embed/'.$m[1];
                            } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $videoUrl, $m)) {
                                $embedUrl = 'https://player.vimeo.com/video/'.$m[1];
                            }
                        @endphp
                        <section class="xp-card reveal-on-scroll" style="margin-top:1.15rem;">
                            <h2 class="xp-card__title">Berater &amp; Video</h2>
                            <p class="xp-card__sub">Persönlicher Ansprechpartner und weiterführende Inhalte.</p>
                            <div class="xp-consultant">
                                @if ($expert->contact_photo_path)
                                    <img class="xp-consultant__photo" src="{{ asset('storage/'.$expert->contact_photo_path) }}" alt="{{ $contactName !== '' ? $contactName : $expert->company_name }}">
                                @endif
                                <div class="xp-consultant__meta">
                                    @if ($contactName !== '')
                                        <strong>{{ $contactName }}</strong>
                                    @endif
                                    <span>{{ \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist') }}</span>
                                    @if ($videoUrl && ! $embedUrl)
                                        <a class="xp-stage__video" href="{{ $videoUrl }}" target="_blank" rel="noopener">Video ansehen ↗</a>
                                    @endif
                                </div>
                            </div>
                            @if ($embedUrl)
                                <div class="xp-consultant__video">
                                    <iframe src="{{ $embedUrl }}" title="Video {{ $expert->company_name }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                </div>
                            @endif
                        </section>
                    @endif

                    @if ($isInterim)
                        <section class="xp-card reveal-on-scroll">
                            <h2 class="xp-card__title">Profil Ad Interim</h2>
                            <p class="xp-card__sub">Future Owner / Ad Interim Nachfolge — diskret und prüfbar.</p>
                            @if (filled($expert->equity_capital))
                                <p style="margin:0 0 0.75rem;"><strong>Eigenkapital:</strong> {{ $expert->equity_capital }}</p>
                            @endif
                            @if (!empty($expert->experience_areas))
                                <p style="margin:0 0 0.35rem;"><strong>Erfahrungsbereiche</strong></p>
                                <ul style="margin:0 0 0.85rem;padding-left:1.1rem;">
                                    @foreach ($expert->experience_areas as $area)<li>{{ $area }}</li>@endforeach
                                </ul>
                            @endif
                            @if (!empty($expert->diplomas))
                                <p style="margin:0 0 0.35rem;"><strong>Diplome</strong></p>
                                <ul style="margin:0 0 0.85rem;padding-left:1.1rem;">
                                    @foreach ($expert->diplomas as $diploma)<li>{{ $diploma }}</li>@endforeach
                                </ul>
                            @endif
                            @if (!empty($expert->interim_criteria))
                                <p style="margin:0 0 0.35rem;"><strong>Kriterien</strong></p>
                                <ul style="margin:0 0 0.85rem;padding-left:1.1rem;">
                                    @foreach ($expert->interim_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach
                                </ul>
                            @endif
                            @if ($expert->description)
                                <div class="xp-prose" style="margin-top:0.5rem;">
                                    <p>{{ $expert->description }}</p>
                                </div>
                            @endif
                        </section>
                        @if ($office)
                            <section class="xp-card reveal-on-scroll" style="margin-top:1.15rem;">
                                <h2 class="xp-card__title">Standort</h2>
                                <div class="xp-office">
                                    <div class="xp-office__pin">{{ $office->canton->code ?? 'CH' }}</div>
                                    <div>
                                        <p class="xp-office__text">
                                            {{ $office->city }}
                                            @if ($office->canton) · {{ $office->canton->name_de }}@endif
                                        </p>
                                    </div>
                                </div>
                            </section>
                        @endif
                    @elseif ($isMarketplace)
                        <section class="xp-card reveal-on-scroll">
                            <h2 class="xp-card__title">Digitaler Marktplatz</h2>
                            <p class="xp-card__sub">Diese Plattform ist schweizweit tätig — nicht an einen einzelnen Kanton gebunden.</p>
                            <div class="xp-office">
                                <div class="xp-office__pin">CH</div>
                                <div>
                                    <p class="xp-office__label">Reichweite</p>
                                    <p class="xp-office__text">
                                        Mitte der Schweiz · national verfügbar<br>
                                        Für Käufer und Verkäufer in der gesamten Schweiz
                                    </p>
                                    @if ($website)
                                        <p class="xp-office__web">
                                            <a href="{{ $website }}" target="_blank" rel="noopener">{{ $websiteHost }} ↗</a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            @if ($expert->description)
                                <div class="xp-prose" style="margin-top:1rem;">
                                    <p>{{ $expert->description }}</p>
                                </div>
                            @endif
                        </section>
                    @else
                        <section class="xp-card reveal-on-scroll">
                            <h2 class="xp-card__title">Standort</h2>
                            <p class="xp-card__sub">Öffentlich sichtbar: Name, Adresse, Ort und Logo — gemäss Plattformregeln.</p>
                            @forelse ($offices as $loc)
                                <div class="xp-office">
                                    <div class="xp-office__pin">{{ $loc->canton->code ?? 'CH' }}</div>
                                    <div>
                                        <p class="xp-office__label">{{ $loc->is_primary ? 'Hauptsitz' : ($loc->label ?: 'Standort') }}</p>
                                        <p class="xp-office__text">
                                            {{ $loc->address_line }}<br>
                                            {{ $loc->postal_code }} {{ $loc->city }}
                                            @if ($loc->canton)<br>{{ $loc->canton->name_de }}@endif
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p style="color:var(--stone);margin:0;">Keine Standortdaten hinterlegt.</p>
                            @endforelse
                        </section>
                    @endif

                    @if (($media ?? collect())->isNotEmpty())
                        <section class="xp-card reveal-on-scroll" style="margin-top:1.15rem;">
                            <h2 class="xp-card__title">Impressionen</h2>
                            <p class="xp-card__sub">Weitere Einblicke in das Unternehmen.</p>
                            <div class="xp-listing-gallery">
                                @foreach ($media as $item)
                                    <figure class="xp-listing-gallery__item">
                                        <img src="{{ asset('storage/'.$item->path) }}" alt="{{ $item->caption ?: $expert->company_name }}">
                                    </figure>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if (($articles ?? collect())->isNotEmpty())
                        <section class="xp-card reveal-on-scroll" style="margin-top:1.15rem;">
                            <h2 class="xp-card__title">Fachbeiträge</h2>
                            <p class="xp-card__sub">Aktuelle Beiträge dieses Experten.</p>
                            <div class="xp-articles">
                                @foreach ($articles as $article)
                                    <a class="xp-article" href="{{ route('blog.show', $article->slug) }}">
                                        <strong>{{ $article->title }}</strong>
                                        <span>
                                            @if ($article->published_at){{ $article->published_at->format('d.m.Y') }} · @endif
                                            Weiterlesen →
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                @unless ($isMarketplace)
                    <aside class="xp-card xp-card--lift xp-card--inquiry xp-sticky reveal-on-scroll" id="kontakt">
                        <div class="xp-form-head">
                            <div>
                                <h2 class="xp-card__title">Vertrauliche Anfrage</h2>
                                <p class="xp-card__sub">Nur an {{ $expert->company_name }} — diskret &amp; kostenlos.</p>
                            </div>
                            <span class="xp-lock">Privat</span>
                        </div>

                        <form method="post" action="{{ route('leads.store', $expert->slug) }}" class="xp-inquiry">
                            @csrf
                            <div class="xp-inquiry__grid">
                                <div class="form-group xp-inquiry__span2">
                                    <label for="owner_name">Ihr Name *</label>
                                    <input class="form-control" type="text" name="owner_name" id="owner_name" value="{{ old('owner_name') }}" required autocomplete="name">
                                    @error('owner_name')<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="email">E-Mail *</label>
                                    <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
                                    @error('email')<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="phone">Telefon</label>
                                    <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone') }}" autocomplete="tel">
                                </div>
                                <div class="form-group">
                                    <label for="company_name">Unternehmen</label>
                                    <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name') }}">
                                </div>
                                <div class="form-group">
                                    <label for="industry">Branche</label>
                                    <input class="form-control" type="text" name="industry" id="industry" value="{{ old('industry') }}">
                                </div>
                                <div class="form-group">
                                    <label for="canton_id">Ihr Kanton</label>
                                    <select class="form-control" name="canton_id" id="canton_id">
                                        <option value="">— optional —</option>
                                        @foreach (\App\Models\Canton::query()->orderBy('name_de')->get() as $canton)
                                            <option value="{{ $canton->id }}" @selected(old('canton_id') == $canton->id)>{{ $canton->name_de }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="buy_sell_context">Anliegen</label>
                                    <select class="form-control" name="buy_sell_context" id="buy_sell_context">
                                        <option value="">— wählen —</option>
                                        <option value="buy" @selected(old('buy_sell_context') === 'buy')>Unternehmen kaufen</option>
                                        <option value="sell" @selected(old('buy_sell_context') === 'sell')>Verkauf / Nachfolge</option>
                                        <option value="both" @selected(old('buy_sell_context') === 'both')>Beides / Beratung</option>
                                    </select>
                                </div>
                                <div class="form-group xp-inquiry__span2">
                                    <label for="message">Nachricht *</label>
                                    <textarea class="form-control" name="message" id="message" rows="3" required placeholder="Kurz Ihr Anliegen…">{{ old('message') }}</textarea>
                                    @error('message')<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn--block">Anfrage vertraulich senden</button>
                            <p class="xp-form-note">Antwort in der Regel innerhalb von 1–2 Werktagen.</p>
                        </form>
                    </aside>
                @endunless
            </div>
        </div>
    </div>
</div>
@endsection
