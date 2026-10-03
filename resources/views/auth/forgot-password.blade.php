@extends('layouts.public')

@section('title', 'Passwort vergessen')

@section('content')
<div class="auth-shell">
    <div class="form-panel auth-card reveal">
        <p class="page-kicker">Zugang</p>
        <h1 class="page-title" style="font-size:2rem;">Passwort zurücksetzen</h1>
        <p class="page-lead" style="margin-bottom:1.5rem;">Wir senden Ihnen einen sicheren Link an Ihre hinterlegte E-Mail-Adresse.</p>

        @include('partials.flash')

        @if ($errors->any())
            <div class="flash flash--error" style="margin-bottom:1rem;">
                <ul class="flash__list" style="margin:0;padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
            </div>
            <button type="submit" class="btn btn--block">Link per E-Mail senden</button>
        </form>
        <p style="margin-top:1.25rem;"><a href="{{ route('login') }}">Zurück zum Login</a></p>
    </div>
</div>
@endsection
