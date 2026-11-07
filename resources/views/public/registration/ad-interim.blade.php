@extends('layouts.public')

@section('title', 'Ad Interim registrieren')
@section('flush', true)
@section('body_class', 'is-auth-page is-register-page')

@section('content')
<div class="register-stage">
    <aside class="register-stage__visual" aria-label="Vorteile der Ad-Interim-Registrierung">
        <div class="register-stage__glow" aria-hidden="true"></div>
        <div class="register-stage__grid" aria-hidden="true"></div>

        <div class="register-stage__brand">
            <img src="{{ asset('logo-nachfolge-experten/logo-nachfolge-experten.svg') }}" alt="" width="56" height="50">
            <p class="register-stage__brand-name">nachfolge-experten.ch</p>
            <h1>Als Ad Interim / Future Owner sichtbar werden</h1>
            <p class="register-stage__lead">
                Persönliche Registrierung für natürliche Personen — mit CV, Eigenkapital und Profil.
                Ideal, wenn Sie operativ einsteigen und ein Unternehmen später übernehmen möchten.
            </p>
        </div>

        <ul class="register-stage__benefits">
            <li>
                <span class="register-stage__benefit-n">01</span>
                <div>
                    <strong>Privatperson, kein Firmenkonto</strong>
                    <span>Vorname, Nachname, Wohnort und CV — nicht die Firmen-Registrierung.</span>
                </div>
            </li>
            <li>
                <span class="register-stage__benefit-n">02</span>
                <div>
                    <strong>Eigenkapital &amp; Erfahrung</strong>
                    <span>Öffentlich diskret als z.&nbsp;B. «Markus Z, Zürich, 200kCHF Eigenkapital».</span>
                </div>
            </li>
            <li>
                <span class="register-stage__benefit-n">03</span>
                <div>
                    <strong>Manuelle Prüfung</strong>
                    <span>In der Regel 24–48 Stunden — danach Login und Profilpflege.</span>
                </div>
            </li>
        </ul>
    </aside>

    <div class="register-stage__panel">
        <div class="register-shell">
            @include('partials.flash')

            <header class="register-shell__head">
                <p class="register-shell__kicker">Ad Interim · Future Owner</p>
                <h2>Persönliches Profil anlegen</h2>
                <p class="register-shell__lead">
                    Für Kandidaten mit CV. Firmen / Berater nutzen die
                    <a href="{{ route('registration.create') }}">Experten-Registrierung</a>.
                </p>
            </header>

            <nav class="register-steps" aria-label="Formularschritte">
                <a href="#step-person" class="register-steps__item is-active" data-step="1">
                    <span>1</span> Person
                </a>
                <a href="#step-profile" class="register-steps__item" data-step="2">
                    <span>2</span> Profil &amp; CV
                </a>
                <a href="#step-access" class="register-steps__item" data-step="3">
                    <span>3</span> Zugang
                </a>
            </nav>

            <form method="post" action="{{ route('registration.ad-interim.store') }}" enctype="multipart/form-data" class="register-form" id="adInterimRegisterForm">
                @csrf

                <section class="register-section" id="step-person" data-step-panel="1">
                    <div class="register-section__head">
                        <span class="register-section__num">01</span>
                        <div>
                            <h3>Person &amp; Wohnort</h3>
                            <p>Angaben zur natürlichen Person — nicht zur Firma.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_person_name">Vorname *</label>
                            <input class="form-control @error('contact_person_name') is-invalid @enderror" type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name') }}" required autocomplete="given-name" placeholder="z. B. Markus">
                            @error('contact_person_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="contact_person_last_name">Nachname *</label>
                            <input class="form-control @error('contact_person_last_name') is-invalid @enderror" type="text" name="contact_person_last_name" id="contact_person_last_name" value="{{ old('contact_person_last_name') }}" required autocomplete="family-name" placeholder="z. B. Zimmermann">
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
                            <input class="form-control @error('phone') is-invalid @enderror" type="text" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel">
                            @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">PLZ *</label>
                            <input class="form-control @error('postal_code') is-invalid @enderror" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" required autocomplete="postal-code">
                            @error('postal_code')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="city">Ort *</label>
                            <input class="form-control @error('city') is-invalid @enderror" type="text" name="city" id="city" value="{{ old('city') }}" required autocomplete="address-level2" placeholder="z. B. Zürich">
                            @error('city')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="canton_id">Kanton *</label>
                            <select class="form-control @error('canton_id') is-invalid @enderror" name="canton_id" id="canton_id" required>
                                <option value="">— wählen —</option>
                                @foreach ($cantons as $canton)
                                    <option value="{{ $canton->id }}" @selected((string) old('canton_id') === (string) $canton->id)>
                                        {{ $canton->code }} — {{ $canton->name_de }}
                                    </option>
                                @endforeach
                            </select>
                            @error('canton_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="address_line">Strasse <em class="label-optional">(optional)</em></label>
                            <input class="form-control" type="text" name="address_line" id="address_line" value="{{ old('address_line') }}" autocomplete="street-address">
                        </div>
                    </div>
                </section>

                <section class="register-section" id="step-profile" data-step-panel="2">
                    <div class="register-section__head">
                        <span class="register-section__num">02</span>
                        <div>
                            <h3>Profil, Eigenkapital &amp; CV</h3>
                            <p>Was Suchende und Unternehmen über Sie wissen sollen.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="equity_capital">Eigenkapital / Investitionsmöglichkeit *</label>
                        <input class="form-control @error('equity_capital') is-invalid @enderror" type="text" name="equity_capital" id="equity_capital" value="{{ old('equity_capital') }}" required placeholder="z. B. 200kCHF">
                        <p class="field-hint">Erscheint öffentlich in der Ad-Interim-Liste.</p>
                        @error('equity_capital')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Kurzportrait <em class="label-optional">(optional)</em></label>
                        <textarea class="form-control" name="description" id="description" rows="4" placeholder="Wer Sie sind, was Sie suchen, Branchenfokus…">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="experience_areas">Erfahrungsbereiche <em class="label-optional">(eine Zeile pro Eintrag)</em></label>
                            <textarea class="form-control" name="experience_areas" id="experience_areas" rows="3" placeholder="Maschinenbau&#10;Medtech">{{ old('experience_areas') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="diplomas">Diplome / Ausbildungen</label>
                            <textarea class="form-control" name="diplomas" id="diplomas" rows="3">{{ old('diplomas') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="interim_criteria">Kriterien / Präferenzen</label>
                        <textarea class="form-control" name="interim_criteria" id="interim_criteria" rows="3" placeholder="z. B. KMU 5–50 MA, Deutschschweiz">{{ old('interim_criteria') }}</textarea>
                    </div>

                    <label class="register-choice">
                        <input type="checkbox" name="intends_purchase" value="1" @checked(old('intends_purchase'))>
                        <span class="register-choice__body">
                            <span class="register-choice__tag">Future Owner</span>
                            <strong>Ich möchte als Ad Interim einsteigen und das Unternehmen später übernehmen.</strong>
                        </span>
                    </label>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="register-upload" for="contact_photo">
                                <input type="file" name="contact_photo" id="contact_photo" accept="image/*" class="register-upload__input">
                                <span class="register-upload__icon" aria-hidden="true"></span>
                                <span class="register-upload__title">Foto hochladen</span>
                                <span class="register-upload__meta" id="photoFileName">PNG oder JPG, max. 4 MB</span>
                            </label>
                            @error('contact_photo')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label class="register-upload" for="cv">
                                <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx,application/pdf" class="register-upload__input">
                                <span class="register-upload__icon" aria-hidden="true"></span>
                                <span class="register-upload__title">CV hochladen</span>
                                <span class="register-upload__meta" id="cvFileName">PDF oder Word, max. 8 MB</span>
                            </label>
                            @error('cv')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="register-section" id="step-access" data-step-panel="3">
                    <div class="register-section__head">
                        <span class="register-section__num">03</span>
                        <div>
                            <h3>Zugang</h3>
                            <p>Passwort für Ihr späteres Login nach Freigabe.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Passwort *</label>
                            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" id="password" required autocomplete="new-password" minlength="8">
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Passwort bestätigen *</label>
                            <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" minlength="8">
                        </div>
                    </div>
                </section>

                <div class="register-submit">
                    <p class="register-submit__note">Mit dem Absenden bestätigen Sie, dass die Angaben korrekt sind. Der Basiseintrag ist kostenlos und wird manuell geprüft.</p>
                    <button type="submit" class="btn btn--block register-submit__btn">Ad-Interim-Registrierung einreichen</button>
                    <p class="register-submit__login">
                        Firma / Berater?
                        <a href="{{ route('registration.create') }}">Zur Experten-Registrierung</a>
                        · Bereits registriert? <a href="{{ route('login') }}">Zum Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('.register-steps__item'));
    links.forEach(function (link) {
        link.addEventListener('click', function () {
            links.forEach(function (l) { l.classList.remove('is-active'); });
            link.classList.add('is-active');
        });
    });
    function bindFile(id, labelId) {
        var input = document.getElementById(id);
        var label = document.getElementById(labelId);
        if (!input || !label) return;
        input.addEventListener('change', function () {
            label.textContent = (input.files && input.files[0]) ? input.files[0].name : label.getAttribute('data-default') || label.textContent;
            input.closest('.register-upload').classList.toggle('has-file', !!(input.files && input.files[0]));
        });
    }
    bindFile('contact_photo', 'photoFileName');
    bindFile('cv', 'cvFileName');
})();
</script>
@endsection
