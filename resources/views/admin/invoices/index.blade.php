@extends('layouts.admin')

@section('title', 'Rechnungen')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> · Rechnungen
@endsection

@section('content')
@php
    $statusLabel = ['pending' => 'Offen', 'paid' => 'Bezahlt', 'cancelled' => 'Storniert', 'overdue' => 'Überfällig'];
    $pill = ['pending' => 'warning', 'paid' => 'success', 'cancelled' => 'muted', 'overdue' => 'danger'];
@endphp

<div class="admin-page-hero">
    <div>
        <p class="page-kicker">Manuelle Abrechnung</p>
        <h1>Rechnungen</h1>
        <p>Zahlungsstatus nach manueller Rechnungsstellung pflegen.</p>
    </div>
</div>

<form method="get" class="admin-toolbar sheet">
    <div class="admin-search">
        <select class="form-control" name="payment_status">
            <option value="">Zahlungsstatus</option>
            @foreach ($paymentStatuses as $ps)
                <option value="{{ $ps }}" @selected(($paymentStatus ?? '') === $ps)>{{ $statusLabel[$ps] ?? $ps }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--small">Filtern</button>
    </div>
</form>

@if ($invoices->isEmpty())
    <div class="sheet"><div class="empty-state" style="border:none;margin:0;">Keine Rechnungen.</div></div>
@else
    <div class="sheet">
        <div class="sheet__head"><h2>{{ $invoices->total() }} Rechnungen</h2></div>
        <table class="entity-table">
            <thead><tr><th>Rechnung</th><th>Experte</th><th>Betrag</th><th>Status</th><th>Aktion</th></tr></thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td>
                            <p class="entity-name">{{ $invoice->invoice_number ?? '#'.$invoice->id }}</p>
                            <p class="entity-sub">{{ optional($invoice->invoice_date)->format('d.m.Y') ?: '—' }}</p>
                        </td>
                        <td>{{ $invoice->expert->company_name ?? '—' }}</td>
                        <td><strong>{{ number_format((float) $invoice->amount, 2, '.', "'") }}</strong> {{ $invoice->currency }}</td>
                        <td><span class="pill pill--{{ $pill[$invoice->payment_status] ?? 'muted' }}">{{ $statusLabel[$invoice->payment_status] ?? $invoice->payment_status }}</span></td>
                        <td>
                            <form method="post" action="{{ route('admin.invoices.update-payment-status', $invoice) }}" style="display:flex;gap:0.4rem;align-items:center;justify-content:flex-end;">
                                @csrf
                                @method('PATCH')
                                <select name="payment_status" class="form-control" style="width:auto;min-width:140px;">
                                    @foreach ($paymentStatuses as $ps)
                                        <option value="{{ $ps }}" @selected($invoice->payment_status === $ps)>{{ $statusLabel[$ps] ?? $ps }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn--small">Speichern</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sheet__footer">{{ $invoices->links() }}</div>
    </div>
@endif
@endsection
