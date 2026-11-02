@extends('layouts.admin')

@section('title', 'Rechnung '.$invoice->invoice_number)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.invoices.index') }}">Rechnungen</a>
    <span>/</span>
    <span>{{ $invoice->invoice_number }}</span>
@endsection

@section('content')
<div class="admin-page" style="max-width:820px;">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">{{ $invoice->expert->company_name ?? '—' }}</p>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>
                Ablauf: 1) Angaben anpassen → 2) Bestätigung senden → 3) Rechnung senden.
                @if ($invoice->confirmation_sent_at)
                    <br><span class="pill pill--success">Bestätigung {{ $invoice->confirmation_sent_at->format('d.m.Y H:i') }}</span>
                @endif
                @if ($invoice->invoice_sent_at)
                    <span class="pill pill--success">Rechnung {{ $invoice->invoice_sent_at->format('d.m.Y H:i') }}</span>
                @endif
            </p>
        </div>
    </header>

    <form method="post" action="{{ route('admin.invoices.update', $invoice) }}" class="card">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="invoice_product_id">Produkt</label>
            <select class="form-control" name="invoice_product_id" id="invoice_product_id">
                <option value="">—</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((int) old('invoice_product_id', $invoice->invoice_product_id) === (int) $product->id)>
                        {{ $product->name }} ({{ number_format((float) $product->unit_price, 2, '.', "'") }} CHF)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="billing_email">Rechnungs-E-Mail</label>
                <input class="form-control" type="email" name="billing_email" id="billing_email" value="{{ old('billing_email', $invoice->billing_email ?: $invoice->expert?->email) }}">
            </div>
            <div class="form-group">
                <label for="invoice_date">Rechnungsdatum</label>
                <input class="form-control" type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Betrag</label>
                <input class="form-control" type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount', $invoice->amount) }}" required>
            </div>
            <div class="form-group">
                <label for="currency">Währung</label>
                <input class="form-control" type="text" name="currency" id="currency" value="{{ old('currency', $invoice->currency ?: 'CHF') }}" maxlength="3">
            </div>
        </div>
        <div class="form-group">
            <label for="service_title">Leistungstitel</label>
            <input class="form-control" type="text" name="service_title" id="service_title" value="{{ old('service_title', $invoice->service_title) }}">
        </div>
        <div class="form-group">
            <label for="service_detail">Leistungsdetail</label>
            <textarea class="form-control" name="service_detail" id="service_detail" rows="3">{{ old('service_detail', $invoice->service_detail) }}</textarea>
        </div>
        <div class="form-group">
            <label for="notes">Notiz auf Rechnung</label>
            <textarea class="form-control" name="notes" id="notes" rows="2">{{ old('notes', $invoice->notes) }}</textarea>
        </div>
        <div class="form-group">
            <label for="admin_notes">Interne Admin-Notiz</label>
            <textarea class="form-control" name="admin_notes" id="admin_notes" rows="2">{{ old('admin_notes', $invoice->admin_notes) }}</textarea>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>

    <div class="admin-card" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.65rem;align-items:center;">
        <form method="post" action="{{ route('admin.invoices.send-confirmation', $invoice) }}">
            @csrf
            <button type="submit" class="btn">1) Bestätigung senden</button>
        </form>
        <form method="post" action="{{ route('admin.invoices.send-email', $invoice) }}">
            @csrf
            <input type="hidden" name="type" value="created">
            <button type="submit" class="btn {{ $invoice->confirmation_sent_at ? '' : 'btn--ghost' }}">2) Rechnung senden</button>
        </form>
        <a class="btn btn--ghost" href="{{ route('admin.invoices.index') }}">Zur Liste</a>
    </div>
</div>
@endsection
