@extends('layouts.public')

@section('title', 'Login')
@section('flush', true)
@section('body_class', 'is-auth-page')

@section('content')
<div class="login-stage">
    <div class="login-stage__visual" aria-hidden="true">
        <div class="login-stage__glow"></div>
        <div class="login-stage__copy">
            <img src="{{ asset('logo-nachfolge-experten/nachfolge-experten-weiss.svg') }}" alt="" width="56" height="50">
            <p class="login-stage__kicker">nachfolge-experten.ch</p>
            <h1>Zugang für Experten &amp; Admin</h1>
            <p>Profil, Anfragen und Inhalte — sicher und mandantenspezifisch.</p>
        </div>
    </div>

    <div class="login-stage__panel">
        <div class="login-box">
            @include('partials.flash')

            <p class="login-box__kicker">Anmelden</p>
            <h2>Willkommen zurück</h2>
            <p class="login-box__lead">E-Mail und Passwort Ihres Kontos.</p>

            <form method="post" action="{{ route('login.submit') }}" class="login-box__form">
                @csrf
                <div class="form-group">
                    <label for="email">E-Mail</label>
                    <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Passwort</label>
                    <input class="form-control" type="password" name="password" id="password" required autocomplete="current-password">
                </div>
                <label class="login-box__remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Angemeldet bleiben</span>
                </label>
                <button type="submit" class="btn btn--block">Anmelden</button>
            </form>

            <div class="login-box__links">
                <a href="{{ route('password.request') }}">Passwort vergessen?</a>
                <a href="{{ route('registration.create') }}">Als Experte registrieren</a>
            </div>
        </div>
    </div>
</div>
@endsection
