@extends('layouts.expert')

@section('title', 'Profil')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Unternehmen</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
        'disabled' => 'Deaktiviert',
    ];
    $statusPill = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disabled' => 'muted',
    ];
@endphp

<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Unternehmen</p>
            <h1>Profil bearbeiten</h1>
            <p>Öffentlich sichtbar sind Name, Adresse, Ort und Logo. Weitere Felder steuern Ihr Dashboard und Anfragen.</p>
        </div>
    </header>

    @if (!$expert)
        <div class="xp-empty">Kein Profil vorhanden.</div>
    @else
        <div class="xp-profile-layout">
            <form method="post" action="{{ route('expert.profile.update') }}" enctype="multipart/form-data" class="xp-panel">
                @csrf
                @method('PUT')

                <div class="xp-panel__head">
                    <div>
                        <h2>Stammdaten</h2>
                        <p>Firma und Erreichbarkeit.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="company_name">Firma *</label>
                    <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name', $expert->company_name) }}" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $expert->email) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', $expert->phone) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="website">Website</label>
                    <input class="form-control" type="url" name="website" id="website" value="{{ old('website', $expert->website) }}" placeholder="https://">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_person_name">Ansprechpartner Vorname</label>
                        <input class="form-control" type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name', $expert->contact_person_name) }}">
                    </div>
                    <div class="form-group">
                        <label for="contact_person_last_name">Nachname</label>
                        <input class="form-control" type="text" name="contact_person_last_name" id="contact_person_last_name" value="{{ old('contact_person_last_name', $expert->contact_person_last_name) }}">
                    </div>
                </div>

                <div class="xp-panel__head" style="margin-top:0.5rem;">
                    <div>
                        <h2>Darstellung &amp; Leistungen</h2>
                        <p>Texte und Fokus für Ihr öffentliches Profil.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Beschreibung</label>
                    <textarea class="form-control" name="description" id="description" rows="5">{{ old('description', $expert->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label for="services_text">Leistungen / Spezialisierungen</label>
                    <textarea class="form-control" name="services_text" id="services_text" rows="3">{{ old('services_text', $expert->services_text) }}</textarea>
                </div>

                <fieldset class="register-focus" style="margin-bottom:1.15rem;">
                    <legend>Beratungsschwerpunkt</legend>
                    <div class="register-focus__grid">
                        <label class="register-focus__card">
                            <input type="checkbox" name="offers_buy" value="1" @checked(old('offers_buy', $expert->offers_buy))>
                            <span class="register-focus__card-body">
                                <span class="register-focus__tag">Buy</span>
                                <strong>Kaufberatung</strong>
                                <span>Unterstützung für Käufer</span>
                            </span>
                        </label>
                        <label class="register-focus__card">
                            <input type="checkbox" name="offers_sell" value="1" @checked(old('offers_sell', $expert->offers_sell))>
                            <span class="register-focus__card-body">
                                <span class="register-focus__tag">Sell</span>
                                <strong>Verkaufsberatung</strong>
                                <span>Begleitung von Veräusserungen</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="logo">Logo aktualisieren</label>
                    <label class="register-upload" for="logo">
                        <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="register-upload__input">
                        <span class="register-upload__icon" aria-hidden="true"></span>
                        <span class="register-upload__title">Logo hochladen</span>
                        <span class="register-upload__meta" id="logoFileName">PNG, JPG oder WebP · max. 4 MB</span>
                    </label>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="gallery">Weitere Bilder für Ihr Listing <em style="font-style:normal;color:var(--stone);font-weight:400;">(optional)</em></label>
                    <p class="field-hint" style="margin-top:0;">Bis zu 6 Bilder für Ihr öffentliches Profil — Team, Büro oder Projekte.</p>

                    @if (($media ?? collect())->isNotEmpty())
                        <div class="xp-media-grid">
                            @foreach ($media as $item)
                                <label class="xp-media-tile">
                                    <img src="{{ asset('storage/'.$item->path) }}" alt="">
                                    <span>
                                        <input type="checkbox" name="remove_media[]" value="{{ $item->id }}">
                                        Entfernen
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @if (($media ?? collect())->count() < 6)
                        <label class="register-upload" for="gallery" style="margin-top:0.75rem;">
                            <input class="register-upload__input" type="file" name="gallery[]" id="gallery" accept="image/png,image/jpeg,image/webp,image/gif" multiple>
                            <span class="register-upload__icon" aria-hidden="true"></span>
                            <span class="register-upload__title">Bilder hinzufügen</span>
                            <span class="register-upload__meta" id="galleryFileName">Mehrfachauswahl möglich · je max. 4 MB</span>
                        </label>
                    @endif
                    @error('gallery')<p class="field-error">{{ $message }}</p>@enderror
                    @error('gallery.*')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="xp-crawl-hint">
                    <div>
                        <strong>Website-Import</strong>
                        <p>Beiträge von Ihrer Website übernehmen Sie im eigenen Import-Bereich.</p>
                    </div>
                    <a class="btn btn--ghost btn--small" href="{{ route('expert.crawl.index') }}">Zum Import</a>
                </div>

                <button type="submit" class="btn">Änderungen speichern</button>
            </form>

            <aside class="xp-side">
                <section class="xp-panel">
                    <div class="xp-panel__head"><div><h2>Status</h2></div></div>
                    <p><span class="pill pill--{{ $statusPill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span></p>
                    @if ($expert->logo_path)
                        <img class="xp-logo-preview" src="{{ asset('storage/'.$expert->logo_path) }}?v={{ optional($expert->updated_at)->timestamp }}" alt="Logo">
                    @else
                        <p class="xp-muted">Noch kein Logo hinterlegt.</p>
                    @endif
                    <p class="xp-muted">Öffentliche Vorschau nur bei freigegebenen, öffentlichen Profilen.</p>
                    @if ($expert->is_public && $expert->status === 'approved')
                        <a class="btn btn--ghost btn--small" href="{{ route('experts.show', $expert->slug) }}" target="_blank" rel="noopener">Öffentliches Profil</a>
                    @endif
                </section>
            </aside>
        </div>
    @endif
</div>

<script>
(function () {
    var logo = document.getElementById('logo');
    var nameEl = document.getElementById('logoFileName');
    if (logo && nameEl) {
        logo.addEventListener('change', function () {
            var file = logo.files && logo.files[0];
            nameEl.textContent = file ? file.name : 'PNG, JPG oder WebP · max. 4 MB';
            logo.closest('.register-upload').classList.toggle('has-file', !!file);
        });
    }

    var gallery = document.getElementById('gallery');
    var galleryName = document.getElementById('galleryFileName');
    if (gallery && galleryName) {
        gallery.addEventListener('change', function () {
            var count = gallery.files ? gallery.files.length : 0;
            galleryName.textContent = count
                ? count + ' Datei(en) ausgewählt'
                : 'Mehrfachauswahl möglich · je max. 4 MB';
            gallery.closest('.register-upload').classList.toggle('has-file', count > 0);
        });
    }
})();
</script>
@endsection
