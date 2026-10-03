@extends('layouts.admin')

@section('title', 'Vorlage bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.email-templates.index') }}">E-Mail-Vorlagen</a>
    <span>/</span>
    <span>{{ $template->key }}</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">E-Mail</p>
            <h1><code>{{ $template->key }}</code></h1>
            <p>Nur den Inhaltsblock bearbeiten — Absender-Header, Markenfarben und Footer werden automatisch ergänzt.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.email-templates.index') }}">Alle Vorlagen</a>
        </div>
    </header>

    @include('partials.flash')

    <form method="post" action="{{ route('admin.email-templates.update', $template) }}" class="admin-card">
        @csrf
        @method('PUT')
        <div class="admin-card__head">
            <h2>Inhalt</h2>
            <p>Platzhalter wie <code>@{{owner_name}}</code> bleiben erhalten.</p>
        </div>
        <div style="padding:1.25rem;">
            <div class="form-group">
                <label for="subject">Betreff</label>
                <input class="form-control" type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" required>
            </div>
            <div class="form-group">
                <label for="body_html">HTML-Inhalt (innerer Block)</label>
                <textarea class="form-control" name="body_html" id="body_html" rows="16" required style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:0.82rem;line-height:1.45;">{{ old('body_html', $template->body_html) }}</textarea>
            </div>
            <div class="form-group">
                <label for="body_text">Text-Inhalt <span class="label-optional">(optional, Plain Text)</span></label>
                <textarea class="form-control" name="body_text" id="body_text" rows="6">{{ old('body_text', $template->body_text) }}</textarea>
            </div>
            <button type="submit" class="btn">Speichern</button>
        </div>
    </form>
</div>
@endsection
