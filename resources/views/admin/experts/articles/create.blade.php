@extends('layouts.admin')

@section('title', 'Fachbeitrag · '.$expert->company_name)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.show', $expert) }}">{{ $expert->company_name }}</a>
    <span>/</span>
    <span>Neuer Fachbeitrag</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Für Experte</p>
            <h1>Neuer Fachbeitrag</h1>
            <p>{{ $expert->company_name }} — Beitrag erscheint unter diesem Expertenprofil.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}#artikel">← Zurück</a>
        </div>
    </header>

    @include('expert.articles._ai-panel', [
        'aiGenerateUrl' => route('admin.experts.articles.generate-ai', $expert),
    ])

    <form method="post" action="{{ route('admin.experts.articles.store', $expert) }}" enctype="multipart/form-data" class="card" id="articleForm">
        @csrf
        @include('expert.articles._form', ['article' => null])
        <div style="margin-top:1rem;display:flex;gap:0.65rem;flex-wrap:wrap;">
            <button type="submit" class="btn">Veröffentlichen</button>
            <a class="btn btn--ghost" href="{{ route('admin.experts.show', $expert) }}#artikel">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
