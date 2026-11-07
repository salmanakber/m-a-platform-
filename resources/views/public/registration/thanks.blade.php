@extends('layouts.public')

@section('title', 'Registrierung eingereicht')
@section('flush', true)
@section('body_class', 'is-auth-page is-register-page')

@section('content')
@php $isInterim = ($kind ?? 'company') === 'ad_interim'; @endphp
<div class="thanks-stage">
    <div class="thanks-stage__glow" aria-hidden="true"></div>
    <div class="thanks-stage__grid" aria-hidden="true"></div>

    <div class="thanks-card">
        <div class="thanks-card__mark" aria-hidden="true">
            <span class="thanks-card__check"></span>
        </div>

        <p class="thanks-card__kicker">nachfolge-experten.ch</p>
        <h1>Vielen Dank{{ $company ? ' — '.$company : '' }}</h1>
        <p class="thanks-card__lead">
            @if ($isInterim)
                Ihre Ad-Interim- / Future-Owner-Registrierung wurde erfolgreich eingereicht und wird manuell geprüft.
            @else
                Ihre Experten-Registrierung wurde erfolgreich eingereicht und wird manuell geprüft.
            @endif
            In der Regel dauert das <strong>24–48 Stunden</strong>.
        </p>

        <ol class="thanks-next">
            <li>
                <span class="thanks-next__n">01</span>
                <div>
                    <strong>Prüfung</strong>
                    <span>
                        @if ($isInterim)
                            Unser Team kontrolliert Person, CV und Angaben.
                        @else
                            Unser Team kontrolliert Profil und Angaben.
                        @endif
                    </span>
                </div>
            </li>
            <li>
                <span class="thanks-next__n">02</span>
                <div>
                    <strong>Freigabe-E-Mail</strong>
                    <span>
                        @if ($email)
                            Nach Freigabe erhalten Sie eine Nachricht an <em>{{ $email }}</em>.
                        @else
                            Nach Freigabe erhalten Sie eine Bestätigung per E-Mail.
                        @endif
                    </span>
                </div>
            </li>
            <li>
                <span class="thanks-next__n">03</span>
                <div>
                    <strong>{{ $isInterim ? 'Sichtbar unter Ad Interim' : 'Sichtbar im Verzeichnis' }}</strong>
                    <span>
                        @if ($isInterim)
                            Ihr Profil erscheint in der Ad-Interim-Auswahl und im Verzeichnis.
                        @else
                            Ihr Profil erscheint regional in Suche, Karte und Kantonen.
                        @endif
                    </span>
                </div>
            </li>
        </ol>

        <div class="thanks-card__actions">
            @if ($isInterim)
                <a class="btn" href="{{ route('directory.index', ['gruppe' => 'ad_interim']) }}">Ad Interim Verzeichnis</a>
            @else
                <a class="btn" href="{{ route('directory.index') }}">Expertenverzeichnis</a>
            @endif
            <a class="btn btn--ghost" href="{{ route('home') }}">Zur Startseite</a>
        </div>
    </div>
</div>
@endsection
