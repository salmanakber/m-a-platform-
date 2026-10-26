@extends('layouts.admin')

@section('title', 'Fachbeitrag bearbeiten · '.$expert->company_name)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.show', $expert) }}">{{ $expert->company_name }}</a>
    <span>/</span>
    <span>Beitrag bearbeiten</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">{{ $expert->company_name }}</p>
            <h1>{{ $article->title }}</h1>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}#artikel">← Zurück</a>
        </div>
    </header>

    <form method="post" action="{{ route('admin.experts.articles.update', [$expert, $article]) }}" enctype="multipart/form-data" class="card" id="articleForm">
        @csrf
        @method('PUT')
        @include('expert.articles._form', ['article' => $article])
        <div style="margin-top:1rem;display:flex;gap:0.65rem;flex-wrap:wrap;">
            <button type="submit" name="action" value="publish" class="btn">Speichern &amp; veröffentlichen</button>
            <button type="submit" name="action" value="draft" class="btn btn--ghost">Als Entwurf</button>
            <a class="btn btn--ghost" href="{{ route('admin.experts.show', $expert) }}#artikel">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
