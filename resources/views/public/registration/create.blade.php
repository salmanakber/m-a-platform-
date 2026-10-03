@extends('layouts.public')

@section('title', 'Als Experte registrieren')
@section('flush', true)
@section('body_class', 'is-auth-page is-register-page')

@section('content')
<div class="register-stage">
    <aside class="register-stage__visual" aria-label="Vorteile der Registrierung">
        <div class="register-stage__glow" aria-hidden="true"></div>
        <div class="register-stage__grid" aria-hidden="true"></div>

        <div class="register-stage__brand">
            <img src="{{ asset('logo-nachfolge-experten/nachfolge-experten-weiss.svg') }}" alt="" width="56" height="50">
            <p class="register-stage__brand-name">nachfolge-experten.ch</p>
            <h1>Werden Sie sichtbar für Nachfolge-Suchende</h1>
            <p class="register-stage__lead">Kostenloser Basiseintrag im Schweizer M&amp;A-Verzeichnis — manuell geprüft, regional gefunden.</p>
        </div>

        <ul class="register-stage__benefits">
            <li>
                <span class="register-stage__benefit-n">01</span>
                <div>
                    <strong>Profil einreichen</strong>
                    <span>Firma, Standort und Schwerpunkt in wenigen Minuten hinterlegen.</span>
                </div>
            </li>
            <li>
                <span class="register-stage__benefit-n">02</span>
                <div>
                    <strong>Manuelle Prüfung</strong>
                    <span>In der Regel 24–48 Stunden — Qualität vor Quantität.</span>
                </div>
            </li>
            <li>
                <span class="register-stage__benefit-n">03</span>
                <div>
                    <strong>Regional sichtbar</strong>
                    <span>Auffindbar in Verzeichnis, Karte und Kantonsseiten.</span>
                </div>
            </li>
        </ul>
    </aside>

    <div class="register-stage__panel">
        <div class="register-shell">
            @include('partials.flash')

            <header class="register-shell__head">
                <p class="register-shell__kicker">Experten-Registrierung</p>
                <h2>Profil anlegen</h2>
                <p class="register-shell__lead">Alle Pflichtfelder ausfüllen. Nach Prüfung erhalten Sie eine Freigabe per E-Mail.</p>
            </header>

            <nav class="register-steps" aria-label="Formularschritte">
                <a href="#step-company" class="register-steps__item is-active" data-step="1">
                    <span>1</span> Unternehmen
                </a>
                <a href="#step-location" class="register-steps__item" data-step="2">
                    <span>2</span> Standort
                </a>
                <a href="#step-access" class="register-steps__item" data-step="3">
                    <span>3</span> Zugang
                </a>
            </nav>

            <form method="post" action="{{ route('registration.store') }}" enctype="multipart/form-data" class="register-form" id="expertRegisterForm">
                @csrf

                <section class="register-section" id="step-company" data-step-panel="1">
                    <div class="register-section__head">
                        <span class="register-section__num">01</span>
                        <div>
                            <h3>Unternehmen</h3>
                            <p>Firmenidentität und Ansprechpartner für Anfragen.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Firmenname *</label>
                        <input class="form-control @error('company_name') is-invalid @enderror" type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required autocomplete="organization" placeholder="z. B. Muster M&A AG">
                        @error('company_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_person_name">Kontaktperson Vorname *</label>
                            <input class="form-control @error('contact_person_name') is-invalid @enderror" type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name') }}" required autocomplete="given-name">
                            @error('contact_person_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="contact_person_last_name">Nachname *</label>
                            <input class="form-control @error('contact_person_last_name') is-invalid @enderror" type="text" name="contact_person_last_name" id="contact_person_last_name" value="{{ old('contact_person_last_name') }}" required autocomplete="family-name">
                            @error('contact_person_last_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-Mail *</label>
                            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefon *</label>
                            <input class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+41 …">
                            @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input class="form-control @error('website') is-invalid @enderror" type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://www.beispiel.ch" autocomplete="url">
                        @error('website')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Kurzbeschreibung</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="4" placeholder="Schwerpunkte, Erfahrung, typische Mandate…">{{ old('description') }}</textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label for="services_text">Leistungen / Spezialisierungen</label>
                        <textarea class="form-control @error('services_text') is-invalid @enderror" name="services_text" id="services_text" rows="3" placeholder="z. B. Due Diligence, Bewertung, Nachfolgeplanung">{{ old('services_text') }}</textarea>
                        @error('services_text')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <fieldset class="register-focus">
                        <legend>Beratungsschwerpunkt</legend>
                        <p class="register-focus__hint">Wählen Sie, wofür Sie sichtbar sein möchten.</p>
                        <div class="register-focus__grid">
                            <label class="register-focus__card">
                                <input type="checkbox" name="offers_buy" value="1" @checked(old('offers_buy', true))>
                                <span class="register-focus__card-body">
                                    <span class="register-focus__tag">Buy</span>
                                    <strong>Unterstützung beim Kauf</strong>
                                    <span>Beratung für Käufer und Investoren</span>
                                </span>
                            </label>
                            <label class="register-focus__card">
                                <input type="checkbox" name="offers_sell" value="1" @checked(old('offers_sell', true))>
                                <span class="register-focus__card-body">
                                    <span class="register-focus__tag">Sell</span>
                                    <strong>Unterstützung beim Verkauf</strong>
                                    <span>Begleitung von Veräusserungen &amp; Nachfolge</span>
                                </span>
                            </label>
                        </div>
                    </fieldset>
                </section>

                <section class="register-section" id="step-location" data-step-panel="2">
                    <div class="register-section__head">
                        <span class="register-section__num">02</span>
                        <div>
                            <h3>Hauptstandort</h3>
                            <p>Wird für Karte und regionale Suche verwendet.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address_line">Strasse / Nr. *</label>
                        <input class="form-control @error('address_line') is-invalid @enderror" type="text" name="address_line" id="address_line" value="{{ old('address_line') }}" required autocomplete="street-address">
                        @error('address_line')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-row form-row--3">
                        <div class="form-group">
                            <label for="postal_code">PLZ *</label>
                            <input class="form-control @error('postal_code') is-invalid @enderror" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" required autocomplete="postal-code">
                            @error('postal_code')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="city">Ort *</label>
                            <input class="form-control @error('city') is-invalid @enderror" type="text" name="city" id="city" value="{{ old('city') }}" required autocomplete="address-level2">
                            @error('city')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="canton_id">Kanton *</label>
                            <select class="form-control @error('canton_id') is-invalid @enderror" name="canton_id" id="canton_id" required>
                                <option value="">— wählen —</option>
                                @foreach ($cantons as $canton)
                                    <option value="{{ $canton->id }}" @selected(old('canton_id') == $canton->id)>{{ $canton->name_de }} ({{ $canton->code }})</option>
                                @endforeach
                            </select>
                            @error('canton_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo <span class="label-optional">(optional)</span></label>
                        <label class="register-upload" for="logo">
                            <input type="file" name="logo" id="logo" accept="image/*" class="register-upload__input">
                            <span class="register-upload__icon" aria-hidden="true"></span>
                            <span class="register-upload__title">Logo hochladen</span>
                            <span class="register-upload__meta" id="logoFileName">PNG oder JPG, max. 2 MB</span>
                        </label>
                        @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="register-section" id="step-access" data-step-panel="3">
                    <div class="register-section__head">
                        <span class="register-section__num">03</span>
                        <div>
                            <h3>Zugang</h3>
                            <p>Für Ihr Experten-Konto nach der Freigabe.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Passwort *</label>
                            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" id="password" required autocomplete="new-password" minlength="8">
                            <p class="field-hint">Mindestens 8 Zeichen</p>
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Passwort bestätigen *</label>
                            <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" minlength="8">
                        </div>
                    </div>
                </section>

                <div class="register-submit">
                    <p class="register-submit__note">Mit dem Absenden bestätigen Sie, dass die Angaben korrekt sind. Der Basiseintrag ist kostenlos.</p>
                    <button type="submit" class="btn btn--block register-submit__btn">Registrierung einreichen</button>
                    <p class="register-submit__login">Bereits registriert? <a href="{{ route('login') }}">Zum Login</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('.register-steps__item'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-step-panel]'));
    var logo = document.getElementById('logo');
    var logoName = document.getElementById('logoFileName');

    if (logo && logoName) {
        logo.addEventListener('change', function () {
            logoName.textContent = logo.files && logo.files[0]
                ? logo.files[0].name
                : 'PNG oder JPG, max. 2 MB';
            logo.closest('.register-upload').classList.toggle('has-file', !!(logo.files && logo.files[0]));
        });
    }

    function setActive(step) {
        links.forEach(function (link) {
            link.classList.toggle('is-active', String(link.getAttribute('data-step')) === String(step));
        });
    }

    if ('IntersectionObserver' in window && panels.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    setActive(entry.target.getAttribute('data-step-panel'));
                }
            });
        }, { rootMargin: '-35% 0px -50% 0px', threshold: 0.01 });

        panels.forEach(function (panel) { observer.observe(panel); });
    }

    links.forEach(function (link) {
        link.addEventListener('click', function () {
            setActive(link.getAttribute('data-step'));
        });
    });
})();
</script>
@endsection
