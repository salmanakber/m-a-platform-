@extends('layouts.public')

@section('title', $expert->company_name.' — nachfolge-experten.ch')
@section('meta_description', 'Vertrauliche Kontaktaufnahme mit '.$expert->company_name.' über nachfolge-experten.ch')
@section('flush')
@endsection

@section('content')
@php
    $offices = $offices ?? collect([$office])->filter();
@endphp

<div class="page-main--flush">
    <div class="container" style="padding-top:1rem;">
        @include('partials.flash')
    </div>
    <section class="xp-stage">
        <div class="container">
            <a class="xp-stage__crumb" href="{{ route('directory.index') }}">← Expertenverzeichnis</a>
            <div class="xp-stage__grid reveal-on-scroll">
                <div class="xp-mark" aria-hidden="true">
                    @if ($expert->logo_path)
                        <img src="{{ asset('storage/'.$expert->logo_path) }}" alt="">
                    @else
                        <span class="xp-mark__letter">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="xp-stage__meta">
                    <p class="xp-stage__eyebrow">M&amp;A · Unternehmensnachfolge · Schweiz</p>
                    <h1 class="xp-stage__name">{{ $expert->company_name }}</h1>
                    @if ($office)
                        <p class="xp-stage__place">
                            {{ $office->address_line }}<br>
                            {{ $office->postal_code }} {{ $office->city }}
                            @if ($office->canton)
                                · {{ $office->canton->name_de }}
                            @endif
                        </p>
                    @endif
                    <div class="xp-stage__chips">
                        @if ($expert->offers_buy)<span class="xp-chip">Kauf</span>@endif
                        @if ($expert->offers_sell)<span class="xp-chip">Verkauf</span>@endif
                        @if ($office && $office->canton)<span class="xp-chip">{{ $office->canton->code }}</span>@endif
                        <span class="xp-chip">Geprüft</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="xp-body">
        <div class="container">
            @include('partials.flash')
            <div class="xp-layout">
                <div>
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
            </div>
        </div>
    </div>
</div>
@endsection
