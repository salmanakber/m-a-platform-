@extends('layouts.public')

@section('title', 'Vielen Dank')

@section('content')
<div class="auth-shell">
    <div class="form-panel thank-panel reveal-on-scroll">
        <p class="page-kicker">Bestätigung</p>
        <h1 class="page-title" style="font-size:2.15rem;">Anfrage eingegangen</h1>
        <p class="page-lead" style="margin-bottom:1.75rem;">
            Vielen Dank. Wir haben Ihre Kontaktdaten vertraulich
            @if ($expert)
                an <strong>{{ $expert->company_name }}</strong>
            @else
                an die ausgewählte M&A-Firma
            @endif
            weitergeleitet. In der Regel meldet sich ein Spezialist innerhalb von 1–2 Werktagen bei Ihnen.
        </p>
        <a class="btn" href="{{ route('directory.index') }}">Zurück zum Verzeichnis</a>
    </div>
</div>
@endsection
