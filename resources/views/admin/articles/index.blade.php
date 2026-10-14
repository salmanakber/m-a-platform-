@extends('layouts.admin')

@section('title', 'Artikel')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Artikel</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'published' => 'Veröffentlicht',
        'blocked' => 'Gesperrt',
        'draft' => 'Entwurf',
    ];
    $pill = [
        'published' => 'success',
        'blocked' => 'danger',
        'draft' => 'muted',
    ];
    $chipBase = array_filter([
        'q' => ($q ?? '') !== '' ? $q : null,
        'expert_id' => ($expertId ?? '') !== '' ? $expertId : null,
    ]);
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Inhalte</p>
            <h1>Artikel</h1>
            <p>Expertenbeiträge und Crawl-Importe prüfen, sperren, deaktivieren oder löschen.</p>
        </div>
    </header>

    @include('partials.flash')

    <div class="status-help">
        <strong>Status</strong>
        <span><em>Gesperrt</em> = öffentlich deaktiviert. <em>Entwurf</em> = nicht sichtbar. Löschen entfernt den Beitrag dauerhaft.</span>
    </div>

    <div class="admin-status-chips" role="group" aria-label="Status schnell filtern">
        <a class="admin-status-chip {{ ($status ?? '') === '' ? 'is-on' : '' }}" href="{{ route('admin.articles.index', $chipBase) }}">Alle</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'published' ? 'is-on' : '' }}" href="{{ route('admin.articles.index', $chipBase + ['status' => 'published']) }}">Veröffentlicht</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'draft' ? 'is-on' : '' }}" href="{{ route('admin.articles.index', $chipBase + ['status' => 'draft']) }}">Entwurf</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'blocked' ? 'is-on' : '' }}" href="{{ route('admin.articles.index', $chipBase + ['status' => 'blocked']) }}">Gesperrt</a>
    </div>

    <form method="get" class="admin-filterbar">
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="q">Suche</label>
                <input class="form-control" type="search" name="q" id="q" value="{{ $q ?? '' }}" placeholder="Titel oder Auszug…">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" name="status" id="status">
                    <option value="">Alle</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="expert_id">Experte</label>
                <select class="form-control" name="expert_id" id="expert_id">
                    <option value="">Alle</option>
                    @foreach ($experts as $ex)
                        <option value="{{ $ex->id }}" @selected((string) ($expertId ?? '') === (string) $ex->id)>{{ $ex->company_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($expertId ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.articles.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($articles->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">
                <p><strong>Keine Artikel gefunden.</strong></p>
                <p>Passen Sie die Filter an oder warten Sie auf neue Expertenbeiträge.</p>
            </div>
        </div>
    @else
        <form method="post" action="{{ route('admin.articles.bulk') }}" id="articlesBulkForm" class="admin-bulk-form">
            @csrf
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $articles->total() }} Artikel</h2>
                    <p>Seite {{ $articles->currentPage() }} von {{ max(1, $articles->lastPage()) }} · Mehrfachauswahl auf dieser Seite</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="action" id="bulkAction">
                        <option value="">Bulk-Aktion…</option>
                        <option value="block">Sperren / Deaktivieren</option>
                        <option value="unblock">Veröffentlichen</option>
                        <option value="draft">Als Entwurf</option>
                        <option value="delete">Löschen</option>
                    </select>
                    <button type="submit" class="btn btn--small">Ausführen</button>
                </div>
            </div>
        </form>

        <div class="admin-card admin-card--flush-top">
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" id="checkAll" form="articlesBulkForm" aria-label="Alle auf dieser Seite auswählen">
                            </th>
                            <th>Beitrag</th>
                            <th>Experte</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $article->id }}" class="row-check" form="articlesBulkForm">
                                </td>
                                <td>
                                    <p class="entity-name">{{ $article->title }}</p>
                                    <p class="entity-sub">
                                        {{ optional($article->published_at)->format('d.m.Y') ?: optional($article->created_at)->format('d.m.Y') }}
                                        @if ($article->category) · {{ $article->category->name_de }} @endif
                                        @if ($article->is_crawled) · Crawl-Import @endif
                                    </p>
                                </td>
                                <td>{{ $article->expert->company_name ?? '—' }}</td>
                                <td>
                                    <span class="pill pill--{{ $pill[$article->status] ?? 'muted' }}">
                                        {{ $statusLabel[$article->status] ?? $article->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions row-actions--stack">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.articles.edit', $article) }}">Bearbeiten</a>
                                        @if ($article->status !== 'blocked')
                                            <form action="{{ route('admin.articles.block', $article) }}" method="post">
                                                @csrf
                                                <button type="submit" class="btn btn--ghost btn--small">Sperren</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.articles.unblock', $article) }}" method="post">
                                                @csrf
                                                <button type="submit" class="btn btn--small">Entsperren</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.articles.destroy', $article) }}" method="post" onsubmit="return confirm('Artikel wirklich löschen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot admin-pager-footer">
                <p class="admin-pager-meta">{{ $articles->firstItem() }}–{{ $articles->lastItem() }} von {{ $articles->total() }}</p>
                {{ $articles->links() }}
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    var bulkForm = document.getElementById('articlesBulkForm');
    var all = document.getElementById('checkAll');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (el) {
                el.checked = all.checked;
            });
        });
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var action = document.getElementById('bulkAction').value;
            var checked = document.querySelectorAll('.row-check:checked').length;
            if (!action) { e.preventDefault(); alert('Bitte eine Bulk-Aktion wählen.'); return; }
            if (!checked) { e.preventDefault(); alert('Bitte mindestens einen Artikel auswählen.'); return; }
            var labels = { block: 'sperren', unblock: 'veröffentlichen', draft: 'als Entwurf setzen', delete: 'löschen' };
            if (!confirm(checked + ' Artikel wirklich «' + (labels[action] || action) + '»?')) e.preventDefault();
        });
    }
})();
</script>
@endsection
