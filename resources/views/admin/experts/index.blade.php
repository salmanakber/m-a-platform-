@extends('layouts.admin')

@section('title', 'Experten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Experten</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
        'disabled' => 'Deaktiviert',
    ];
    $pill = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disabled' => 'muted',
    ];
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Verwaltung</p>
            <h1>Experten</h1>
            <p>Registrierungen und Imports prüfen — Freigabe macht Profile öffentlich sichtbar.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.import.index') }}">Import</a>
            <a class="btn btn--small" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">Nur ausstehend</a>
        </div>
    </header>

    @include('partials.flash')

    <div class="status-help">
        <strong>Status «Ausstehend»</strong>
        <span>Neue Registrierungen und Imports starten immer als pending und sind nicht öffentlich. Freigeben setzt Status auf freigegeben + öffentlich. Ablehnen oder Deaktivieren hält sie unsichtbar.</span>
    </div>

    <div class="admin-status-chips" role="group" aria-label="Status schnell filtern">
        <a class="admin-status-chip {{ ($status ?? '') === '' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Alle</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'pending' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'pending', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Ausstehend</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'approved' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'approved', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Freigegeben</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'rejected' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'rejected', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Abgelehnt</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'disabled' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'disabled', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Deaktiviert</a>
    </div>

    <form method="get" class="admin-filterbar" novalidate>
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="filter_q">Suche</label>
                <input class="form-control" type="search" name="q" id="filter_q" value="{{ $q ?? '' }}" placeholder="Firma, E-Mail, Kontakt…">
            </div>
            <div class="form-group">
                <label for="filter_status">Status</label>
                <select class="form-control" name="status" id="filter_status">
                    <option value="">Alle</option>
                    <option value="pending" @selected(($status ?? '') === 'pending')>Ausstehend</option>
                    <option value="approved" @selected(($status ?? '') === 'approved')>Freigegeben</option>
                    <option value="rejected" @selected(($status ?? '') === 'rejected')>Abgelehnt</option>
                    <option value="disabled" @selected(($status ?? '') === 'disabled')>Deaktiviert</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_canton_id">Kanton</label>
                <select class="form-control" name="canton_id" id="filter_canton_id">
                    <option value="">Alle</option>
                    @foreach ($cantons as $canton)
                        <option value="{{ $canton->id }}" @selected((string) ($cantonId ?? '') === (string) $canton->id)>{{ $canton->name_de }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($cantonId ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.experts.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($experts->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">
                <p><strong>Keine Experten gefunden.</strong></p>
                <p>Passen Sie die Filter an oder prüfen Sie ausstehende Registrierungen.</p>
            </div>
        </div>
    @else
        <form method="post" action="{{ route('admin.experts.bulk') }}" id="expertsBulkForm" class="admin-bulk-form" novalidate>
            @csrf
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $experts->total() }} Ergebnisse</h2>
                    <p>Seite {{ $experts->currentPage() }} von {{ $experts->lastPage() }}</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="action" id="bulkAction">
                        <option value="">Bulk-Aktion…</option>
                        <option value="approve">Freigeben</option>
                        <option value="reject">Ablehnen</option>
                        <option value="deactivate">Deaktivieren</option>
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
                                <input type="checkbox" id="checkAll" form="expertsBulkForm" aria-label="Alle auswählen" title="Alle auf dieser Seite">
                            </th>
                            <th>Experte</th>
                            <th>Kontakt</th>
                            <th>Fokus</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($experts as $expert)
                            @php $office = $expert->offices->firstWhere('is_primary', true) ?? $expert->offices->first(); @endphp
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $expert->id }}" class="row-check" form="expertsBulkForm">
                                </td>
                                <td>
                                    <div class="entity-primary">
                                        <div class="entity-avatar">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 1)) }}</div>
                                        <div>
                                            <p class="entity-name">{{ $expert->company_name }}</p>
                                            <p class="entity-sub">
                                                @if ($office)
                                                    {{ $office->city }}@if($office->canton) · {{ $office->canton->code }}@endif
                                                @else
                                                    —
                                                @endif
                                                @if ($expert->is_public) · öffentlich @endif
                                                @if ($expert->imported_from) · Import @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $expert->email }}</div>
                                    <div class="entity-sub">{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: '—' }}</div>
                                </td>
                                <td>
                                    <div class="chip-row" style="justify-content:flex-start;">
                                        @if ($expert->offers_buy)<span class="badge badge--buy">Kauf</span>@endif
                                        @if ($expert->offers_sell)<span class="badge badge--sell">Verkauf</span>@endif
                                    </div>
                                </td>
                                <td>
                                    <span class="pill pill--{{ $pill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}">Öffnen</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $experts->links() }}</div>
        </div>
    @endif
</div>

<script>
(function () {
    var bulkForm = document.getElementById('expertsBulkForm');
    var all = document.getElementById('checkAll');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (el) { el.checked = all.checked; });
        });
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var action = document.getElementById('bulkAction').value;
            var checked = document.querySelectorAll('.row-check:checked').length;
            if (!action) { e.preventDefault(); alert('Bitte eine Bulk-Aktion wählen.'); return; }
            if (!checked) { e.preventDefault(); alert('Bitte mindestens einen Experten auswählen.'); return; }
            if (!confirm(checked + ' Experte(n) wirklich «' + action + '»?')) e.preventDefault();
        });
    }
})();
</script>
@endsection
