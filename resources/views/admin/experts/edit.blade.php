@extends('layouts.admin')

@section('title', 'Experte bearbeiten')

@section('content')
    <a href="{{ route('admin.experts.show', $expert) }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">{{ $expert->company_name }} bearbeiten</h1>

    @if (session('success'))
        <div class="alert alert--success" style="margin-bottom:1rem;">{{ session('success') }}</div>
    @endif
    @if (session('credentials_hint'))
        <div class="status-help" style="margin-bottom:1rem;">
            <strong>Hinweis für den Experten</strong>
            <span>Bitte den Experten bitten, auch den <em>Spam-/Junk-Ordner</em> zu prüfen, falls die E-Mail nicht im Posteingang erscheint. Der Reset-Link ist zeitlich begrenzt gültig.</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert--danger" style="margin-bottom:1rem;">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card" id="zugang" style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.35rem;font-size:1.05rem;">Login-Zugang (E-Mail &amp; Passwort)</h2>
        <p class="field-hint" style="margin-top:0;">
            Hier setzen Sie die Anmelde-E-Mail und optional ein Passwort.
            @if (! empty($loginStatusHint))
                <strong>{{ $loginStatusHint }}</strong>
            @endif
        </p>

        <form method="post" action="{{ route('admin.experts.credentials', $expert) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="login_email">Login-E-Mail *</label>
                <input class="form-control" type="email" name="login_email" id="login_email" value="{{ old('login_email', $expert->email) }}" required autocomplete="off">
                <p class="field-hint">Gleichzeitig die Profil-E-Mail des Experten.</p>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Neues Passwort <em class="label-optional">(optional)</em></label>
                    <input class="form-control" type="password" name="password" id="password" minlength="8" autocomplete="new-password" placeholder="Mindestens 8 Zeichen">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Passwort bestätigen</label>
                    <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="send_reset_link" value="1" @checked(old('send_reset_link'))>
                    Zusätzlich Passwort-Reset-Link per E-Mail senden
                </label>
                <p class="field-hint">Empfohlen, wenn Sie kein Passwort setzen — Experte wählt selbst eines. Bitte Spam-Ordner erwähnen.</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                <button type="submit" class="btn">Zugang speichern</button>
            </div>
        </form>

        <form method="post" action="{{ route('admin.experts.password-reset', $expert) }}" style="margin-top:0.85rem;padding-top:0.85rem;border-top:1px solid var(--line,#e5e8e5);" onsubmit="return confirm('Passwort-Reset-Link an {{ $expert->email }} senden?\n\nBitte den Experten darauf hinweisen, auch den Spam-/Junk-Ordner zu prüfen.');">
            @csrf
            <button type="submit" class="btn btn--ghost btn--small">Nur Reset-Link senden</button>
        </form>
    </section>

    <form method="post" action="{{ route('admin.experts.update', $expert) }}" class="card" enctype="multipart/form-data" style="margin-bottom:1.5rem;">
        @csrf
        @method('PUT')

        <h2 style="margin:0 0 1rem;font-size:1.05rem;">Stammdaten</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="company_name">Firma</label>
                <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name', $expert->company_name) }}" required>
            </div>
            <div class="form-group">
                <label for="email">E-Mail (Profil)</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $expert->email) }}" required>
                <p class="field-hint">Für Login-Zugang oben die Sektion «Login-Zugang» nutzen — dort können Sie auch das Passwort setzen.</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', $expert->phone) }}">
            </div>
            <div class="form-group">
                <label for="website">Website</label>
                <input class="form-control" type="url" name="website" id="website" value="{{ old('website', $expert->website) }}" placeholder="https://">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="contact_person_name">Vorname Kontakt</label>
                <input class="form-control" type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name', $expert->contact_person_name) }}">
            </div>
            <div class="form-group">
                <label for="contact_person_last_name">Nachname Kontakt</label>
                <input class="form-control" type="text" name="contact_person_last_name" id="contact_person_last_name" value="{{ old('contact_person_last_name', $expert->contact_person_last_name) }}">
            </div>
        </div>
        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $expert->description) }}</textarea>
        </div>
        <div class="form-group">
            <label for="services_text">Leistungen</label>
            <textarea class="form-control" name="services_text" id="services_text" rows="3">{{ old('services_text', $expert->services_text) }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="professional_group">Fachgruppe</label>
                <select class="form-control" name="professional_group" id="professional_group">
                    @foreach (($professionalGroups ?? []) as $groupKey => $groupLabel)
                        <option value="{{ $groupKey }}" @selected(old('professional_group', $expert->professional_group ?? 'ma_specialist') === $groupKey)>{{ $groupLabel }}</option>
                    @endforeach
                </select>
                <p class="field-hint">M&A, Juristen, Marktplätze oder Weitere — steuert Verzeichnis, Ranking und Kartenfarbe. Dieselbe E-Mail darf für ein zweites Profil (z.&nbsp;B. Marktplatz) verwendet werden.</p>
            </div>
            <div class="form-group">
                <label for="logo_position">Logo-Position</label>
                <select class="form-control" name="logo_position" id="logo_position">
                    @php $logoPos = old('logo_position', $expert->logo_position ?? 'left'); @endphp
                    <option value="left" @selected($logoPos === 'left')>Links</option>
                    <option value="center" @selected($logoPos === 'center')>Mitte</option>
                    <option value="right" @selected($logoPos === 'right')>Rechts</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="public_rating">Öffentliche Bewertung (0–5)</label>
                <input class="form-control" type="number" step="0.1" min="0" max="5" name="public_rating" id="public_rating" value="{{ old('public_rating', $expert->public_rating) }}" placeholder="z. B. 4.9">
            </div>
            <div class="form-group">
                <label for="public_deals_count">Transaktionen / Deals</label>
                <input class="form-control" type="number" min="0" name="public_deals_count" id="public_deals_count" value="{{ old('public_deals_count', $expert->public_deals_count) }}" placeholder="z. B. 63">
            </div>
        </div>
        <div class="form-group">
            <label for="logo">Logo hochladen</label>
            <input class="form-control" type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp,image/gif">
            @if ($expert->logo_path)
                <p style="margin:0.5rem 0 0;"><img src="{{ asset('storage/'.$expert->logo_path) }}" alt="" style="max-height:64px;"></p>
            @endif
            @if ($expert->logo_locked)
                <p class="field-hint">Manuelles Logo — wird bei Crawls nicht überschrieben.</p>
            @endif
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="contact_photo">Foto Berater / Kontaktperson</label>
                <input class="form-control" type="file" name="contact_photo" id="contact_photo" accept="image/png,image/jpeg,image/webp,image/gif">
                @if ($expert->contact_photo_path)
                    <p style="margin:0.5rem 0 0;"><img src="{{ asset('storage/'.$expert->contact_photo_path) }}" alt="" style="max-height:96px;border-radius:8px;"></p>
                    <label style="display:inline-flex;gap:0.35rem;align-items:center;margin-top:0.35rem;">
                        <input type="checkbox" name="remove_contact_photo" value="1"> Foto entfernen
                    </label>
                @endif
            </div>
            <div class="form-group">
                <label for="video_url">Video-Link (YouTube / Vimeo / Website)</label>
                <input class="form-control" type="url" name="video_url" id="video_url" value="{{ old('video_url', $expert->video_url) }}" placeholder="https://…">
                <p class="field-hint">Öffentlich auf dem Expertenprofil verlinkt.</p>
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="offers_buy" value="1" @checked(old('offers_buy', $expert->offers_buy))> Bietet Kaufberatung</label>
            <p class="field-hint">Erscheint im Verzeichnis-Filter «Kauf».</p>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="offers_sell" value="1" @checked(old('offers_sell', $expert->offers_sell))> Bietet Verkaufsberatung</label>
            <p class="field-hint">Erscheint im Verzeichnis-Filter «Verkauf».</p>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $expert->is_public))> Öffentlich sichtbar</label>
            <p class="field-hint">Nur zusammen mit Status «Freigegeben» sichtbar auf Website/Karte. Aus = Profil bleibt intern.</p>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="crawl_enabled" value="1" @checked(old('crawl_enabled', $expert->crawl_enabled))> Website-Crawl aktiv</label>
            <p class="field-hint">Erlaubt dem System, Blog-/News-Inhalte von der Crawl-URL zu holen (als Entwürfe). Braucht auch eine Crawl-URL unten.</p>
        </div>
        <div class="form-group">
            <label for="website_crawl_url">Crawl-URL</label>
            <input class="form-control" type="url" name="website_crawl_url" id="website_crawl_url" value="{{ old('website_crawl_url', $expert->website_crawl_url) }}" placeholder="https://beispiel.ch/blog">
            <p class="field-hint">Seite mit Blog/News (z. B. wie kmu-beratungen.ch/blog). Crawl legt Artikel als Entwurf an — Experte/Admin veröffentlicht sie.</p>
        </div>

        <button type="submit" class="btn">Stammdaten speichern</button>
    </form>

    <section class="card" id="standorte">
        <h2 style="margin:0 0 0.35rem;font-size:1.05rem;">Standorte &amp; Adressen</h2>
        <p class="field-hint" style="margin-top:0;">Mehrere Filialen möglich (z.&nbsp;B. Zürich, Bern, Aarau). Für schweizweite Präsenz Kanton «CH — Ganze Schweiz» wählen.</p>

        @forelse ($expert->offices as $office)
            <details class="admin-office" @if ($errors->any() && (int) old('office_id') === (int) $office->id) open @endif>
                <summary>
                    <strong>{{ $office->label ?: ($office->is_primary ? 'Hauptsitz' : 'Standort') }}</strong>
                    @if ($office->is_primary)
                        <span class="pill pill--success">Haupt</span>
                    @endif
                    <span>
                        {{ $office->address_line }}, {{ $office->postal_code }} {{ $office->city }}
                        @if ($office->canton)
                            · {{ $office->canton->code }}
                        @endif
                    </span>
                </summary>
                <form method="post" action="{{ route('admin.experts.offices.update', [$expert, $office]) }}" style="padding:1rem 0 0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="office_id" value="{{ $office->id }}">
                    @include('expert.offices._fields', ['office' => $office, 'cantons' => $cantons])
                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.75rem;">
                        <button type="submit" class="btn btn--small">Standort speichern</button>
                    </div>
                </form>
                <form method="post" action="{{ route('admin.experts.offices.destroy', [$expert, $office]) }}" onsubmit="return confirm('Standort wirklich löschen?');" style="margin-top:0.75rem;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--ghost btn--small">Standort löschen</button>
                </form>
            </details>
        @empty
            <p style="color:var(--stone);">Noch keine Standorte — unten den ersten erfassen.</p>
        @endforelse

        <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--line, #e5e8e5);">
            <h3 style="margin:0 0 0.75rem;font-size:0.95rem;">{{ $expert->offices->isEmpty() ? 'Hauptsitz erfassen' : 'Weiteren Standort hinzufügen' }}</h3>
            <form method="post" action="{{ route('admin.experts.offices.store', $expert) }}">
                @csrf
                @include('expert.offices._fields', ['office' => null, 'cantons' => $cantons])
                <button type="submit" class="btn" style="margin-top:0.5rem;">Adresse speichern</button>
            </form>
        </div>
    </section>

<style>
.admin-office {
    margin: 0.85rem 0;
    border: 1px solid var(--line, #e5e8e5);
    border-radius: 8px;
    background: #fafbfa;
}
.admin-office > summary {
    cursor: pointer;
    list-style: none;
    display: grid;
    gap: 0.2rem;
    padding: 0.85rem 1rem;
}
.admin-office > summary::-webkit-details-marker { display: none; }
.admin-office > summary span:last-child {
    color: var(--stone);
    font-size: 0.88rem;
}
.admin-office[open] > summary {
    border-bottom: 1px solid var(--line, #e5e8e5);
}
.admin-office form { padding-left: 1rem; padding-right: 1rem; padding-bottom: 1rem; }
</style>

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

    function fillForm(root, item) {
        var form = root.closest('form');
        if (!form) return;
        var address = root.querySelector('[name="address_line"]');
        var postal = form.querySelector('[name="postal_code"]');
        var city = form.querySelector('[name="city"]');
        var canton = form.querySelector('[name="canton_id"]');
        var lat = form.querySelector('[name="latitude"]');
        var lng = form.querySelector('[name="longitude"]');
        var geocode = form.querySelector('[name="geocode"]');

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
        items.forEach(function (item) {
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
