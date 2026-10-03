@extends('layouts.admin')

@section('title', 'Artikel bearbeiten')

@section('content')
    <a href="{{ route('admin.articles.index') }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">{{ $article->title }}</h1>
    <p style="color:var(--stone);">Experte: {{ $article->expert->company_name ?? '—' }}</p>

    <form method="post" action="{{ route('admin.articles.update', $article) }}" class="card">
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
                    <option value="{{ $s }}" @selected(old('status', $article->status) === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>

    <div class="action-bar">
        @if ($article->status !== 'blocked')
            <form action="{{ route('admin.articles.block', $article) }}" method="post">@csrf<button type="submit" class="btn btn--ghost">Sperren</button></form>
        @else
            <form action="{{ route('admin.articles.unblock', $article) }}" method="post">@csrf<button type="submit" class="btn">Entsperren</button></form>
        @endif
        <form action="{{ route('admin.articles.destroy', $article) }}" method="post" onsubmit="return confirm('Artikel wirklich löschen?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn--ghost">Löschen</button>
        </form>
    </div>
@endsection
