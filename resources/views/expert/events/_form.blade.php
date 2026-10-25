<div class="form-group">
    <label for="title">Titel *</label>
    <input class="form-control" type="text" name="title" id="title" value="{{ old('title', optional($event)->title) }}" required>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="event_type">Typ *</label>
        <select class="form-control" name="event_type" id="event_type" required>
            @foreach (['workshop' => 'Workshop', 'presentation' => 'Vortrag', 'hearing' => 'Hearing', 'other' => 'Sonstiges'] as $value => $label)
                <option value="{{ $value }}" @selected(old('event_type', optional($event)->event_type ?? 'workshop') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="source_url">Quellen-URL <em style="font-style:normal;color:var(--stone);font-weight:400;">(optional)</em></label>
        <input class="form-control" type="url" name="source_url" id="source_url" value="{{ old('source_url', optional($event)->source_url) }}" placeholder="https://">
    </div>
</div>
<div class="form-group">
    <label for="description">Beschreibung</label>
    <textarea class="form-control" name="description" id="description" rows="8">{{ old('description', optional($event)->description) }}</textarea>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="starts_on">Datum</label>
        <input class="form-control" type="date" name="starts_on" id="starts_on" value="{{ old('starts_on', optional(optional($event)->starts_on)->format('Y-m-d')) }}">
    </div>
    <div class="form-group">
        <label for="starts_at_time">Uhrzeit</label>
        <input class="form-control" type="time" name="starts_at_time" id="starts_at_time" value="{{ old('starts_at_time', optional($event)->starts_at_time ? \Illuminate\Support\Str::of(optional($event)->starts_at_time)->substr(0,5) : '') }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="location_name">Veranstaltungsort</label>
        <input class="form-control" type="text" name="location_name" id="location_name" value="{{ old('location_name', optional($event)->location_name) }}">
    </div>
    <div class="form-group">
        <label for="city">Ort / Stadt</label>
        <input class="form-control" type="text" name="city" id="city" value="{{ old('city', optional($event)->city) }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="address_line">Adresse</label>
        <input class="form-control" type="text" name="address_line" id="address_line" value="{{ old('address_line', optional($event)->address_line) }}">
    </div>
    <div class="form-group">
        <label for="postal_code">PLZ</label>
        <input class="form-control" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', optional($event)->postal_code) }}">
    </div>
    <div class="form-group">
        <label for="canton_id">Kanton</label>
        <select class="form-control" name="canton_id" id="canton_id">
            <option value="">—</option>
            @foreach ($cantons as $canton)
                <option value="{{ $canton->id }}" @selected((string) old('canton_id', optional($event)->canton_id) === (string) $canton->id)>{{ $canton->name_de }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="latitude">Breitengrad <em style="font-style:normal;color:var(--stone);font-weight:400;">(Karte)</em></label>
        <input class="form-control" type="text" name="latitude" id="latitude" value="{{ old('latitude', optional($event)->latitude) }}">
    </div>
    <div class="form-group">
        <label for="longitude">Längengrad</label>
        <input class="form-control" type="text" name="longitude" id="longitude" value="{{ old('longitude', optional($event)->longitude) }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="price_label">Preis-Hinweis</label>
        <input class="form-control" type="text" name="price_label" id="price_label" value="{{ old('price_label', optional($event)->price_label) }}" placeholder="z. B. Kostenlos / CHF 180">
    </div>
    <div class="form-group">
        <label for="capacity_label">Plätze</label>
        <input class="form-control" type="text" name="capacity_label" id="capacity_label" value="{{ old('capacity_label', optional($event)->capacity_label) }}" placeholder="z. B. 40 Plätze">
    </div>
</div>
<div class="form-group">
    <label for="registration_email">Anmelde-E-Mail <em class="label-optional">(optional)</em></label>
    <input class="form-control" type="email" name="registration_email" id="registration_email" value="{{ old('registration_email', optional($event)->registration_email) }}" placeholder="Leer = Experten-E-Mail">
    <p class="field-hint">Interessenten-Anmeldungen werden an diese Adresse geschickt.</p>
</div>
<div class="form-group">
    <label><input type="checkbox" name="is_top_event" value="1" @checked(old('is_top_event', optional($event)->is_top_event))> Als «Top Event» hervorheben</label>
    <p class="field-hint">Erscheint bevorzugt auf der Startseite, solange das Datum in der Zukunft liegt.</p>
</div>
<p class="field-hint">Keine erfundenen Daten: fehlendes Datum oder Ort leer lassen — wird als prüfungsbedürftig markiert.</p>
