@extends('layouts.admin')

@section('title', 'CV — '.$expert->company_name)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.index') }}">Experten</a>
    <span>/</span>
    <a href="{{ route('admin.experts.show', $expert) }}">{{ $expert->company_name }}</a>
    <span>/</span>
    <span>CV</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">CV Generator</p>
            <h1>Lebensläufe — {{ $expert->company_name }}</h1>
            <p>Als Admin CVs für diesen Experten erzeugen und finalisieren.</p>
        </div>
        <form method="post" action="{{ route('admin.experts.cv.store', $expert) }}">
            @csrf
            <div class="form-group" style="margin:0 0 0.5rem;">
                <select class="form-control" name="template_key">
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <label style="display:flex;gap:0.4rem;align-items:center;margin:0 0 0.5rem;font-size:0.9rem;">
                <input type="checkbox" name="use_ai_template" value="1"> KI-Template
            </label>
            <button class="btn" type="submit">Neuen CV erstellen</button>
        </form>
    </header>

    @forelse ($cvs as $cv)
        <div class="admin-card" style="margin-bottom:0.75rem;display:flex;justify-content:space-between;gap:1rem;align-items:center;">
            <div>
                <strong>{{ $cv->title }}</strong>
                <p class="field-hint" style="margin:0.25rem 0 0;">{{ $templates[$cv->template_key] ?? $cv->template_key }} · {{ $cv->status }} · {{ $cv->updated_at?->format('d.m.Y H:i') }}</p>
            </div>
            <a class="btn btn--small" href="{{ route('admin.experts.cv.edit', [$expert, $cv]) }}">Bearbeiten</a>
        </div>
    @empty
        <div class="empty-state">Noch keine CVs für diesen Experten.</div>
    @endforelse
</div>
@endsection
