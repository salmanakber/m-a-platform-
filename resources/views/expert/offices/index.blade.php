@extends('layouts.expert')

@section('title', 'Standorte')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Standorte</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Profil</p>
            <h1>Standorte &amp; Adresse</h1>
            <p>Hinterlegen Sie Ihre Geschäftsadresse — daraus werden Kartenmarker (Lat/Lng) für das öffentliche Verzeichnis gesetzt.</p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('expert.profile.edit') }}">Unternehmen</a>
        </div>
    </header>

    <div class="xp-action-strip">
        <div>
            <strong>Wichtig für die Karte</strong>
            <p>Ohne Adresse und Kanton erscheint Ihr Profil nicht korrekt auf der Karte. Speichern mit «Koordinaten berechnen» aktiviert.</p>
        </div>
    </div>

    @if ($offices->isNotEmpty())
        <div class="xp-office-list">
            @foreach ($offices as $office)
                <section class="xp-panel">
                    <div class="xp-panel__head">
                        <div>
                            <h2>{{ $office->label ?: 'Standort' }}@if($office->is_primary) <span class="pill pill--success">Hauptstandort</span>@endif</h2>
                            <p>
                                {{ $office->address_line }} · {{ $office->postal_code }} {{ $office->city }}
                                @if ($office->canton) · {{ $office->canton->code }} @endif
                            </p>
                            <p class="xp-muted" style="margin:0.35rem 0 0;">
                                @if ($office->latitude && $office->longitude)
                                    Karte: {{ number_format((float) $office->latitude, 5, '.', '') }}, {{ number_format((float) $office->longitude, 5, '.', '') }}
                                @else
                                    Noch keine Koordinaten — bitte Adresse speichern und Geocoding aktivieren.
                                @endif
                            </p>
                        </div>
                    </div>
                    <details class="xp-office-edit">
                        <summary>Adresse bearbeiten</summary>
                        <form method="post" action="{{ route('expert.offices.update', $office) }}" class="xp-office-edit__form">
                            @csrf
                            @method('PUT')
                            @include('expert.offices._fields', ['office' => $office])
                            <div class="xp-article-panel__actions" style="margin-top:0.75rem;">
                                <button type="submit" class="btn">Standort speichern</button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('expert.offices.destroy', $office) }}" onsubmit="return confirm('Standort löschen?');" class="xp-danger-strip" style="margin-top:1rem;">
                            @csrf
                            @method('DELETE')
                            <div><strong>Standort löschen</strong><p>Entfernt Adresse und Kartenmarker.</p></div>
                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                        </form>
                    </details>
                </section>
            @endforeach
        </div>
    @else
        <div class="xp-empty">
            <strong>Noch keine Adresse hinterlegt</strong>
            <p>Fügen Sie unten Ihren Hauptsitz hinzu, damit Interessenten Sie auf der Karte finden.</p>
        </div>
    @endif

    <section class="xp-panel" style="margin-top:1.15rem;">
        <div class="xp-panel__head">
            <div>
                <h2>{{ $offices->isEmpty() ? 'Hauptsitz erfassen' : 'Weiteren Standort hinzufügen' }}</h2>
                <p>Strasse, PLZ, Ort und Kanton — Koordinaten werden automatisch berechnet.</p>
            </div>
        </div>
        <form method="post" action="{{ route('expert.offices.store') }}" style="padding:0 1.25rem 1.25rem;">
            @csrf
            @include('expert.offices._fields', ['office' => null])
            <button type="submit" class="btn" style="margin-top:0.5rem;">Adresse speichern</button>
        </form>
    </section>
</div>
@endsection
