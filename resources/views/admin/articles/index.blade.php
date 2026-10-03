@extends('layouts.admin')

@section('title', 'Artikel')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> · Artikel
@endsection

@section('content')
@php
    $statusLabel = ['published' => 'Veröffentlicht', 'blocked' => 'Gesperrt', 'draft' => 'Entwurf'];
    $pill = ['published' => 'success', 'blocked' => 'danger', 'draft' => 'muted'];
@endphp

<div class="admin-page-hero">
    <div>
        <p class="page-kicker">Inhalte</p>
        <h1>Artikel</h1>
        <p>Expertenbeiträge und Crawl-Importe prüfen, sperren oder bearbeiten.</p>
    </div>
</div>

<form method="get" class="admin-toolbar sheet">
    <div class="admin-search">
        <input class="form-control" type="search" name="q" value="{{ $q ?? '' }}" placeholder="Titel suchen…">
        <select class="form-control" name="status">
            <option value="">Status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
            @endforeach
        </select>
        <select class="form-control" name="expert_id">
            <option value="">Experte</option>
            @foreach ($experts as $ex)
                <option value="{{ $ex->id }}" @selected((string) ($expertId ?? '') === (string) $ex->id)>{{ $ex->company_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--small">Filtern</button>
    </div>
</form>

@if ($articles->isEmpty())
    <div class="sheet"><div class="empty-state" style="border:none;margin:0;">Keine Artikel gefunden.</div></div>
@else
    <div class="sheet">
        <div class="sheet__head"><h2>{{ $articles->total() }} Artikel</h2></div>
        <table class="entity-table">
            <thead><tr><th>Beitrag</th><th>Experte</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($articles as $article)
                    <tr>
                        <td>
                            <p class="entity-name">{{ $article->title }}</p>
                            <p class="entity-sub">{{ optional($article->published_at)->format('d.m.Y') ?: '—' }}@if($article->is_crawled) · Crawl@importendif</p>
                        </td>
                        <td>{{ $article->expert->company_name ?? '—' }}</td>
                        <td><span class="pill pill--{{ $pill[$article->status] ?? 'muted' }}">{{ $statusLabel[$article->status] ?? $article->status }}</span></td>
                        <td><div class="row-actions"><a class="btn btn--small" href="{{ route('admin.articles.edit', $article) }}">Bearbeiten</a></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sheet__footer">{{ $articles->links() }}</div>
    </div>
@endif
@endsection
