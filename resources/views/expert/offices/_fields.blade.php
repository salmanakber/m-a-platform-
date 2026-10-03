@php $oid = optional($office)->id ?? 'new'; @endphp
<div class="form-group">
    <label for="label_{{ $oid }}">Bezeichnung</label>
    <input class="form-control" type="text" name="label" id="label_{{ $oid }}" value="{{ old('label', optional($office)->label) }}">
</div>
<div class="form-group">
    <label for="address_{{ $oid }}">Adresse</label>
    <input class="form-control" type="text" name="address_line" id="address_{{ $oid }}" value="{{ old('address_line', optional($office)->address_line) }}">
</div>
<div class="form-row">
    <div class="form-group">
        <label>PLZ</label>
        <input class="form-control" type="text" name="postal_code" value="{{ old('postal_code', optional($office)->postal_code) }}">
    </div>
    <div class="form-group">
        <label>Ort</label>
        <input class="form-control" type="text" name="city" value="{{ old('city', optional($office)->city) }}">
    </div>
</div>
<div class="form-group">
    <label>Kanton</label>
    <select class="form-control" name="canton_id" required>
        <option value="">—</option>
        @foreach ($cantons as $canton)
            <option value="{{ $canton->id }}" @selected((string) old('canton_id', optional($office)->canton_id) === (string) $canton->id)>{{ $canton->name_de }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label><input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', optional($office)->is_primary ?? false))> Hauptstandort</label>
</div>
