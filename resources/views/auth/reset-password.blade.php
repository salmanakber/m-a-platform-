@extends('layouts.public')

@section('title', 'Neues Passwort')

@section('content')
<div class="auth-shell">
    <div class="form-panel auth-card reveal">
        <p class="page-kicker">Zugang</p>
        <h1 class="page-title" style="font-size:2rem;">Neues Passwort setzen</h1>
        <p class="page-lead" style="margin-bottom:1.5rem;">Wählen Sie ein neues Passwort für Ihr Konto.</p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">E-Mail</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $email) }}" required autocomplete="username">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="password">Neues Passwort</label>
                <input class="form-control" type="password" name="password" id="password" required autocomplete="new-password">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Passwort bestätigen</label>
                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn--block">Passwort speichern</button>
        </form>
        <p style="margin-top:1.25rem;"><a href="{{ route('login') }}">Zurück zum Login</a></p>
    </div>
</div>
@endsection
