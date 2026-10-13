@extends('layouts.admin')

@section('title', 'Experte bearbeiten')

@section('content')
    <a href="{{ route('admin.experts.show', $expert) }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">{{ $expert->company_name }} bearbeiten</h1>

    <form method="post" action="{{ route('admin.experts.update', $expert) }}" class="card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label for="company_name">Firma</label>
                <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name', $expert->company_name) }}" required>
            </div>
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $expert->email) }}" required>
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
        <div class="form-group">
            <label><input type="checkbox" name="offers_buy" value="1" @checked(old('offers_buy', $expert->offers_buy))> Bietet Kaufberatung</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="offers_sell" value="1" @checked(old('offers_sell', $expert->offers_sell))> Bietet Verkaufsberatung</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $expert->is_public))> Öffentlich sichtbar</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="crawl_enabled" value="1" @checked(old('crawl_enabled', $expert->crawl_enabled))> Website-Crawl aktiv</label>
        </div>
        <div class="form-group">
            <label for="website_crawl_url">Crawl-URL</label>
            <input class="form-control" type="url" name="website_crawl_url" id="website_crawl_url" value="{{ old('website_crawl_url', $expert->website_crawl_url) }}">
        </div>

        <button type="submit" class="btn">Speichern</button>
    </form>
@endsection
