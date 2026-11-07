@extends('layouts.public')

@section('title', 'Login')
@section('flush', true)
@section('body_class', 'is-auth-page is-login-page')

@section('content')
<div class="login-stage">
    <div class="login-box">
        <div class="login-box__brand">
            <img src="{{ asset('logo-nachfolge-experten/logo-nachfolge-experten.svg') }}" alt="" width="40" height="36">
            <span>nachfolge-experten.ch</span>
        </div>

        @include('partials.flash')

        <h1 class="login-box__title">Anmelden</h1>
        <p class="login-box__lead">Zugang für Experten und Ad Interim.</p>

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
        <p class="login-box__alt">
            Ad Interim / Future Owner?
            <a href="{{ route('registration.ad-interim.create') }}">Persönliche Registrierung</a>
        </p>
    </div>
</div>
@endsection
