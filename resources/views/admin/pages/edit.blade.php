@extends('layouts.admin')

@section('title', $page->title)

@section('content')
    <a href="{{ route('admin.pages.index') }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">{{ $page->title }}</h1>

    <form method="post" action="{{ route('admin.pages.update', $page) }}" class="card">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Titel</label>
            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $page->title) }}" required>
        </div>
        <div class="form-group">
            <label for="body">Inhalt (HTML erlaubt)</label>
            <textarea class="form-control" name="body" id="body" rows="16">{{ old('body', $page->body) }}</textarea>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Veröffentlicht</label>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>
@endsection
