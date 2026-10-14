@extends('layouts.admin')

@section('title', 'Artikel bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.articles.index') }}">Artikel</a>
    <span>/</span>
    <span>Bearbeiten</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'published' => 'Veröffentlicht',
        'blocked' => 'Gesperrt',
        'draft' => 'Entwurf',
    ];
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Artikel</p>
            <h1>{{ $article->title }}</h1>
            <p>Experte: {{ $article->expert->company_name ?? '—' }}</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.articles.index') }}">← Zurück</a>
        </div>
    </header>

    @include('partials.flash')

    <form method="post" action="{{ route('admin.articles.update', $article) }}" class="admin-card" style="padding:1.15rem 1.25rem 1.35rem;">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Titel</label>
            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $article->title) }}" required>
        </div>
        <div class="form-group">
            <label for="category_id">Kategorie</label>
            <select class="form-control" name="category_id" id="category_id">
                <option value="">—</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('category_id', $article->category_id) === (string) $cat->id)>{{ $cat->name_de }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="excerpt">Auszug</label>
            <textarea class="form-control" name="excerpt" id="excerpt" rows="2">{{ old('excerpt', $article->excerpt) }}</textarea>
        </div>
        <div class="form-group">
            <label for="body">Inhalt</label>
            <textarea class="form-control" name="body" id="body" rows="10">{{ old('body', $article->body) }}</textarea>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" name="status" id="status">
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(old('status', $article->status) === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>

    <div class="action-bar" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;">
        @if ($article->status !== 'blocked')
            <form action="{{ route('admin.articles.block', $article) }}" method="post">@csrf<button type="submit" class="btn btn--ghost">Sperren / Deaktivieren</button></form>
        @else
            <form action="{{ route('admin.articles.unblock', $article) }}" method="post">@csrf<button type="submit" class="btn">Entsperren</button></form>
        @endif
        <form action="{{ route('admin.articles.destroy', $article) }}" method="post" onsubmit="return confirm('Artikel wirklich löschen?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn--ghost">Löschen</button>
        </form>
    </div>
</div>
@endsection
