@extends('layouts.admin')

@section('title', 'Experte manuell anlegen')

@section('content')
    <a href="{{ route('admin.experts.index') }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">Experte manuell anlegen</h1>
    <p class="field-hint">Ohne Excel-Import — Profil, Adresse und optional Login direkt erfassen.</p>

    @if ($errors->any())
        <div class="alert alert--danger" style="margin-bottom:1rem;">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.experts.store') }}" class="card">
        @csrf

        <h2 style="margin:0 0 1rem;font-size:1.05rem;">Firma &amp; Kontakt</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="company_name">Firma *</label>
                <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required>
            </div>
            <div class="form-group">
                <label for="email">E-Mail / Login *</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}" required>
                <p class="field-hint">Darf dieselbe E-Mail wie ein bestehendes Profil sein (z.&nbsp;B. zusätzlicher Marktplatz) — Login wird dann geteilt.</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Passwort <em class="label-optional">(optional)</em></label>
                <input class="form-control" type="password" name="password" id="password" minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="password_confirmation">Passwort bestätigen</label>
                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" minlength="8" autocomplete="new-password">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="contact_person_name">Vorname</label>
                <input class="form-control" type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name') }}">
            </div>
            <div class="form-group">
                <label for="contact_person_last_name">Nachname</label>
                <input class="form-control" type="text" name="contact_person_last_name" id="contact_person_last_name" value="{{ old('contact_person_last_name') }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone') }}">
            </div>
            <div class="form-group">
                <label for="website">Website</label>
                <input class="form-control" type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://">
            </div>
        </div>
        <div class="form-group">
            <label for="professional_group">Fachgruppe</label>
            <select class="form-control" name="professional_group" id="professional_group">
                @foreach (($professionalGroups ?? []) as $groupKey => $groupLabel)
                    <option value="{{ $groupKey }}" @selected(old('professional_group', 'ma_specialist') === $groupKey)>{{ $groupLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="description">Kurzbeschreibung</label>
            <textarea class="form-control" name="description" id="description" rows="3">{{ old('description') }}</textarea>
        </div>

        <h2 style="margin:1.5rem 0 1rem;font-size:1.05rem;">Hauptsitz</h2>
        <div class="form-group">
            <label for="address_line">Strasse / Nr. *</label>
            <input class="form-control" type="text" name="address_line" id="address_line" value="{{ old('address_line') }}" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="postal_code">PLZ *</label>
                <input class="form-control" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" required>
            </div>
            <div class="form-group">
                <label for="city">Ort *</label>
                <input class="form-control" type="text" name="city" id="city" value="{{ old('city') }}" required>
            </div>
        </div>
        <div class="form-group">
            <label for="canton_id">Kanton *</label>
            <select class="form-control" name="canton_id" id="canton_id" required>
                <option value="">— wählen —</option>
                @foreach ($cantons as $canton)
                    <option value="{{ $canton->id }}" @selected((string) old('canton_id') === (string) $canton->id)>
                        {{ $canton->code }} — {{ $canton->name_de }}
                    </option>
                @endforeach
            </select>
        </div>

        <h2 style="margin:1.5rem 0 0.75rem;font-size:1.05rem;">Optionen</h2>
        <div class="form-group">
            <label><input type="checkbox" name="offers_buy" value="1" @checked(old('offers_buy', true))> Bietet Kaufberatung</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="offers_sell" value="1" @checked(old('offers_sell', true))> Bietet Verkaufsberatung</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="approve_now" value="1" @checked(old('approve_now'))> Sofort freigeben &amp; öffentlich sichtbar</label>
            <p class="field-hint">Sonst bleibt der Experte «Ausstehend» bis zur Freigabe.</p>
        </div>

        <button type="submit" class="btn">Experte anlegen</button>
    </form>
@endsection
