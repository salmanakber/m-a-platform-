@extends('layouts.admin')

@section('title', 'Event · '.$expert->company_name)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.show', $expert) }}">{{ $expert->company_name }}</a>
    <span>/</span>
    <span>Neues Event</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Für Experte</p>
            <h1>Neues Event</h1>
            <p>{{ $expert->company_name }} — Anmeldungen gehen an den Experten.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}#events">← Zurück</a>
        </div>
    </header>

    @include('expert.events._ai-panel', [
        'aiGenerateUrl' => route('admin.experts.events.generate-ai', $expert),
    ])

    <form method="post" action="{{ route('admin.experts.events.store', $expert) }}" class="card">
        @csrf
        @include('expert.events._form', ['event' => null, 'cantons' => $cantons])
        <div style="margin-top:1rem;display:flex;gap:0.65rem;flex-wrap:wrap;">
            <button type="submit" class="btn">Veröffentlichen</button>
            <a class="btn btn--ghost" href="{{ route('admin.experts.show', $expert) }}#events">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
