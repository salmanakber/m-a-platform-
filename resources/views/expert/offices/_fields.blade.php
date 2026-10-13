@php $oid = optional($office)->id ?? 'new'; @endphp
<div class="form-group">
    <label for="label_{{ $oid }}">Bezeichnung <em class="label-optional">(optional)</em></label>
    <input class="form-control" type="text" name="label" id="label_{{ $oid }}" value="{{ old('label', optional($office)->label) }}" placeholder="z. B. Hauptsitz, Filiale Zürich">
</div>

<div class="form-group">
    <label for="address_{{ $oid }}">Strasse / Nr. *</label>
    <input class="form-control" type="text" name="address_line" id="address_{{ $oid }}" value="{{ old('address_line', optional($office)->address_line) }}" required placeholder="Brünigstrasse 144">
</div>

<div class="form-row">
    <div class="form-group">
        <label for="postal_{{ $oid }}">PLZ *</label>
        <input class="form-control" type="text" name="postal_code" id="postal_{{ $oid }}" value="{{ old('postal_code', optional($office)->postal_code) }}" required placeholder="6060">
    </div>
    <div class="form-group">
        <label for="city_{{ $oid }}">Ort *</label>
        <input class="form-control" type="text" name="city" id="city_{{ $oid }}" value="{{ old('city', optional($office)->city) }}" required placeholder="Sarnen">
    </div>
</div>

<div class="form-group">
    <label for="canton_{{ $oid }}">Kanton *</label>
    <select class="form-control" name="canton_id" id="canton_{{ $oid }}" required>
        <option value="">— wählen —</option>
        @foreach ($cantons as $canton)
            <option value="{{ $canton->id }}" @selected((string) old('canton_id', optional($office)->canton_id) === (string) $canton->id)>
                {{ $canton->code }} — {{ $canton->name_de }}
            </option>
        @endforeach
    </select>
</div>

<div class="xp-geo-box">
    <p class="xp-geo-box__title">Kartenposition</p>
    <p class="field-hint" style="margin-top:0;">Wird aus der Adresse automatisch ermittelt. Sie können die Werte bei Bedarf manuell korrigieren.</p>
    <div class="form-row">
        <div class="form-group">
            <label for="lat_{{ $oid }}">Breitengrad (Lat)</label>
            <input class="form-control" type="text" name="latitude" id="lat_{{ $oid }}" value="{{ old('latitude', optional($office)->latitude) }}" placeholder="automatisch" inputmode="decimal">
        </div>
        <div class="form-group">
            <label for="lng_{{ $oid }}">Längengrad (Lng)</label>
            <input class="form-control" type="text" name="longitude" id="lng_{{ $oid }}" value="{{ old('longitude', optional($office)->longitude) }}" placeholder="automatisch" inputmode="decimal">
        </div>
    </div>
    <label class="xp-toggle" style="margin-top:0.35rem;">
        <input type="checkbox" name="geocode" value="1" @checked(old('geocode', optional($office)->latitude === null))>
        <span class="xp-toggle__ui" aria-hidden="true"></span>
        <span class="xp-toggle__copy">
            <strong>Koordinaten aus Adresse berechnen</strong>
            <em>Empfohlen beim Speichern — setzt Lat/Lng für die öffentliche Karte.</em>
        </span>
    </label>
</div>

<div class="form-group" style="margin-top:0.85rem;">
    <label><input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', optional($office)->is_primary ?? ($oid === 'new')))> Hauptstandort (öffentlich primär)</label>
</div>
