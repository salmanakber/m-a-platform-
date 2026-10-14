@extends('layouts.admin')

@section('title', 'Anfragen')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> · Anfragen
@endsection

@section('content')
@php
    $statusLabel = [
        'new' => 'Neu',
        'contacted' => 'Kontaktiert',
        'in_progress' => 'In Bearbeitung',
        'closed' => 'Geschlossen',
    ];
    $pill = [
        'new' => 'info',
        'contacted' => 'warning',
        'in_progress' => 'warning',
        'closed' => 'muted',
    ];
@endphp

<div class="admin-page">
    <div class="admin-page-hero">
        <div>
            <p class="page-kicker">Lead-Management</p>
            <h1>Anfragen</h1>
            <p>Alle Kontaktanfragen an Experten — filtern, Mehrfachauswahl, Status setzen.</p>
        </div>
    </div>

    @include('partials.flash')

    <form method="get" class="admin-filterbar" novalidate>
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="filter_status">Status</label>
                <select class="form-control" name="status" id="filter_status">
                    <option value="">Alle</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="filter_expert_id">Experte</label>
                <select class="form-control" name="expert_id" id="filter_expert_id">
                    <option value="">Alle</option>
                    @foreach ($experts as $ex)
                        <option value="{{ $ex->id }}" @selected((string) ($expertId ?? '') === (string) $ex->id)>{{ $ex->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="filter_date_from">Von</label>
                <input class="form-control" type="date" name="date_from" id="filter_date_from" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="form-group">
                <label for="filter_date_to">Bis</label>
                <input class="form-control" type="date" name="date_to" id="filter_date_to" value="{{ $dateTo ?? '' }}">
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($status ?? '') !== '' || ($expertId ?? '') !== '' || ($dateFrom ?? '') !== '' || ($dateTo ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.leads.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($leads->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">Keine Anfragen für diese Filter.</div>
        </div>
    @else
        <form method="post" action="{{ route('admin.leads.bulk-status') }}" id="leadsBulkForm" class="admin-bulk-form" novalidate>
            @csrf
            @method('PATCH')
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $leads->total() }} Anfragen</h2>
                    <p><span id="leads-selected-count">0</span> ausgewählt · Seite {{ $leads->currentPage() }} von {{ $leads->lastPage() }}</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="status" id="bulk_status">
                        <option value="">Status setzen…</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}">{{ $statusLabel[$s] ?? $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn--small" id="leads-bulk-submit">Anwenden</button>
                </div>
            </div>
        </form>

        <div class="admin-card admin-card--flush-top">
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" id="leads-select-all" form="leadsBulkForm" aria-label="Alle auf dieser Seite auswählen">
                            </th>
                            <th>Interessent</th>
                            <th>Experte</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" class="row-check" name="lead_ids[]" value="{{ $lead->id }}" form="leadsBulkForm" aria-label="Anfrage {{ $lead->id }} auswählen">
                                </td>
                                <td>
                                    <div class="entity-primary">
                                        <div class="entity-avatar">{{ mb_strtoupper(mb_substr($lead->owner_name, 0, 1)) }}</div>
                                        <div>
                                            <p class="entity-name">{{ $lead->owner_name }}</p>
                                            <p class="entity-sub">{{ $lead->email }}@if($lead->phone) · {{ $lead->phone }}@endif</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $lead->expert->company_name ?? '—' }}</td>
                                <td>
                                    <div>{{ $lead->created_at->format('d.m.Y') }}</div>
                                    <div class="entity-sub">{{ $lead->created_at->format('H:i') }}</div>
                                </td>
                                <td><span class="pill pill--{{ $pill[$lead->status] ?? 'muted' }}">{{ $statusLabel[$lead->status] ?? $lead->status }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--small" href="{{ route('admin.leads.show', $lead) }}">Öffnen</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot admin-pager-footer">
                <p class="admin-pager-meta">Seite {{ $leads->currentPage() }} von {{ $leads->lastPage() }} · {{ $leads->total() }} gesamt</p>
                {{ $leads->links() }}
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    var form = document.getElementById('leadsBulkForm');
    if (!form) return;
    var all = document.getElementById('leads-select-all');
    var checks = Array.prototype.slice.call(document.querySelectorAll('.row-check'));
    var countEl = document.getElementById('leads-selected-count');
    var statusEl = document.getElementById('bulk_status');

    function sync() {
        var n = checks.filter(function (c) { return c.checked; }).length;
        if (countEl) countEl.textContent = String(n);
        if (all) {
            all.checked = n > 0 && n === checks.length;
            all.indeterminate = n > 0 && n < checks.length;
        }
    }

    if (all) {
        all.addEventListener('change', function () {
            checks.forEach(function (c) { c.checked = all.checked; });
            sync();
        });
    }
    checks.forEach(function (c) { c.addEventListener('change', sync); });
    form.addEventListener('submit', function (e) {
        var n = checks.filter(function (c) { return c.checked; }).length;
        if (!n || !statusEl.value) {
            e.preventDefault();
            alert(!n ? 'Bitte mindestens eine Anfrage auswählen.' : 'Bitte einen Status wählen.');
            return;
        }
        if (!confirm('Status für die ausgewählten Anfragen ändern?')) e.preventDefault();
    });
    sync();
})();
</script>
@endsection
