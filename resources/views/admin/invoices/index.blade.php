@extends('layouts.admin')

@section('title', 'Rechnungen')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Rechnungen</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Offen',
        'paid' => 'Bezahlt',
        'cancelled' => 'Storniert',
        'overdue' => 'Überfällig',
    ];
    $pill = [
        'pending' => 'warning',
        'paid' => 'success',
        'cancelled' => 'muted',
        'overdue' => 'danger',
    ];
    $chipBase = array_filter([
        'q' => ($q ?? '') !== '' ? $q : null,
        'date_from' => ($dateFrom ?? '') !== '' ? $dateFrom : null,
        'date_to' => ($dateTo ?? '') !== '' ? $dateTo : null,
    ]);
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Manuelle Abrechnung</p>
            <h1>Rechnungen</h1>
            <p>Promotion-Rechnungen erfassen und Zahlungsstatus pflegen — ohne Online-Zahlung.</p>
        </div>
        <div class="admin-page-hero__actions">
            <span class="admin-price-chip">Monatspreis <strong>{{ number_format((float) ($monthlyPrice ?? 0), 2, '.', "'") }} CHF</strong></span>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.promotions.index', ['status' => 'pending']) }}">Offene Promotionen</a>
        </div>
    </header>

    @include('partials.flash')

    <div class="status-help">
        <strong>So funktioniert’s</strong>
        <span>
            1) Experte fragt Promotion an → 2) unter <a href="{{ route('admin.promotions.index') }}">Promotionen</a> «Rechnung» erstellen →
            3) Rechnung extern stellen → 4) hier auf <em>Bezahlt</em> setzen (optional Promotion aktivieren).
        </span>
    </div>

    <div class="admin-status-chips" role="group" aria-label="Zahlungsstatus filtern">
        <a class="admin-status-chip {{ ($paymentStatus ?? '') === '' ? 'is-on' : '' }}" href="{{ route('admin.invoices.index', $chipBase) }}">Alle</a>
        <a class="admin-status-chip {{ ($paymentStatus ?? '') === 'pending' ? 'is-on' : '' }}" href="{{ route('admin.invoices.index', $chipBase + ['payment_status' => 'pending']) }}">Offen</a>
        <a class="admin-status-chip {{ ($paymentStatus ?? '') === 'paid' ? 'is-on' : '' }}" href="{{ route('admin.invoices.index', $chipBase + ['payment_status' => 'paid']) }}">Bezahlt</a>
        <a class="admin-status-chip {{ ($paymentStatus ?? '') === 'overdue' ? 'is-on' : '' }}" href="{{ route('admin.invoices.index', $chipBase + ['payment_status' => 'overdue']) }}">Überfällig</a>
        <a class="admin-status-chip {{ ($paymentStatus ?? '') === 'cancelled' ? 'is-on' : '' }}" href="{{ route('admin.invoices.index', $chipBase + ['payment_status' => 'cancelled']) }}">Storniert</a>
    </div>

    <form method="get" class="admin-filterbar" id="invoiceFilterForm">
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="q">Suche</label>
                <input class="form-control" type="search" name="q" id="q" value="{{ $q ?? '' }}" placeholder="Rechnungsnr. oder Experte…">
            </div>
            <div class="form-group">
                <label for="payment_status">Status</label>
                <select class="form-control" name="payment_status" id="payment_status">
                    <option value="">Alle</option>
                    @foreach ($paymentStatuses as $ps)
                        <option value="{{ $ps }}" @selected(($paymentStatus ?? '') === $ps)>{{ $statusLabel[$ps] ?? $ps }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="date_from">Von</label>
                <input class="form-control" type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="form-group">
                <label for="date_to">Bis</label>
                <input class="form-control" type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}">
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($q ?? '') !== '' || ($paymentStatus ?? '') !== '' || ($dateFrom ?? '') !== '' || ($dateTo ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.invoices.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($invoices->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">
                <p><strong>Keine Rechnungen.</strong></p>
                <p>Erstellen Sie Rechnungen über «Rechnung» bei einer ausstehenden Promotion.</p>
            </div>
        </div>
    @else
        <form method="post" action="{{ route('admin.invoices.bulk') }}" id="invoicesBulkForm" class="admin-bulk-form">
            @csrf
            @method('PATCH')
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $invoices->total() }} Rechnungen</h2>
                    <p>Seite {{ $invoices->currentPage() }} von {{ max(1, $invoices->lastPage()) }}</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="payment_status" id="bulkStatus">
                        <option value="">Status setzen…</option>
                        @foreach ($paymentStatuses as $ps)
                            <option value="{{ $ps }}">{{ $statusLabel[$ps] ?? $ps }}</option>
                        @endforeach
                    </select>
                    <label class="bulk-check">
                        <input type="checkbox" name="activate_promotion" value="1">
                        Bei «Bezahlt» Promotion aktivieren
                    </label>
                    <button type="submit" class="btn btn--small">Ausführen</button>
                </div>
            </div>
        </form>

        <div class="admin-card admin-card--flush-top">
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="checkAll" form="invoicesBulkForm" aria-label="Alle auswählen"></th>
                            <th>Rechnung</th>
                            <th>Experte</th>
                            <th>Promotion</th>
                            <th>Betrag</th>
                            <th>Status</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            @php
                                $promo = $invoice->linkedPromotion ?? $invoice->promotion;
                            @endphp
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $invoice->id }}" class="row-check" form="invoicesBulkForm">
                                </td>
                                <td>
                                    <p class="entity-name">{{ $invoice->invoice_number ?? '#'.$invoice->id }}</p>
                                    <p class="entity-sub">{{ optional($invoice->invoice_date)->format('d.m.Y') ?: '—' }}</p>
                                </td>
                                <td>{{ $invoice->expert->company_name ?? '—' }}</td>
                                <td>
                                    @if ($promo)
                                        <strong>{{ $promo->canton->code ?? '—' }}</strong>
                                        <div class="entity-sub">Pos. {{ $promo->position_number }} · {{ $promo->status }}</div>
                                    @else
                                        <span class="entity-sub">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format((float) $invoice->amount, 2, '.', "'") }}</strong>
                                    {{ $invoice->currency }}
                                </td>
                                <td>
                                    <span class="pill pill--{{ $pill[$invoice->payment_status] ?? 'muted' }}">
                                        {{ $statusLabel[$invoice->payment_status] ?? $invoice->payment_status }}
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="{{ route('admin.invoices.update-payment-status', $invoice) }}" class="invoice-row-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="payment_status" class="form-control">
                                            @foreach ($paymentStatuses as $ps)
                                                <option value="{{ $ps }}" @selected($invoice->payment_status === $ps)>{{ $statusLabel[$ps] ?? $ps }}</option>
                                            @endforeach
                                        </select>
                                        @if ($promo && $promo->status !== 'active')
                                            <label class="bulk-check bulk-check--compact" title="Beim Speichern als Bezahlt die Promotion aktivieren">
                                                <input type="checkbox" name="activate_promotion" value="1">
                                                + aktivieren
                                            </label>
                                        @endif
                                        <button type="submit" class="btn btn--small">Speichern</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot admin-pager-footer">
                <p class="admin-pager-meta">{{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} von {{ $invoices->total() }}</p>
                {{ $invoices->links() }}
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    var bulkForm = document.getElementById('invoicesBulkForm');
    var all = document.getElementById('checkAll');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (el) { el.checked = all.checked; });
        });
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var status = document.getElementById('bulkStatus').value;
            var checked = document.querySelectorAll('.row-check:checked').length;
            if (!status) {
                e.preventDefault();
                alert('Bitte einen Status wählen.');
                return;
            }
            if (!checked) {
                e.preventDefault();
                alert('Bitte mindestens eine Rechnung auswählen.');
                return;
            }
            if (!confirm(checked + ' Rechnung(en) auf «' + status + '» setzen?')) {
                e.preventDefault();
            }
        });
    }
})();
</script>
@endsection
