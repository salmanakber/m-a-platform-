@extends('layouts.admin')

@section('title', 'Rechnung erstellen')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.invoices.index') }}">Rechnungen</a>
    <span>/</span>
    <span>Neu</span>
@endsection

@section('content')
<div class="admin-page" style="max-width:760px;">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Rechnungsstellung</p>
            <h1>Neue Rechnung</h1>
            <p>Produkt wählen, Angaben prüfen — zuerst Bestätigungs-E-Mail, danach Rechnung versenden. Kein Online-Payment.</p>
        </div>
    </header>

    <form method="post" action="{{ route('admin.invoices.store') }}" class="card">
        @csrf
        <div class="form-group">
            <label for="expert_id">Experte *</label>
            <select class="form-control" name="expert_id" id="expert_id" required>
                <option value="">— wählen —</option>
                @foreach ($experts as $expert)
                    <option value="{{ $expert->id }}" @selected((int) old('expert_id') === (int) $expert->id)>
                        {{ $expert->company_name }} ({{ $expert->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="invoice_product_id">Produkt *</label>
            <select class="form-control" name="invoice_product_id" id="invoice_product_id" required>
                <option value="">— wählen —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                            data-price="{{ $product->unit_price }}"
                            data-name="{{ $product->name }}"
                            @selected((int) old('invoice_product_id') === (int) $product->id)>
                        {{ $product->name }} — {{ number_format((float) $product->unit_price, 2, '.', "'") }} {{ $product->currency }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="billing_email">Rechnungs-E-Mail (optional abweichend)</label>
                <input class="form-control" type="email" name="billing_email" id="billing_email" value="{{ old('billing_email') }}" placeholder="Leer = Experten-E-Mail">
            </div>
            <div class="form-group">
                <label for="amount">Betrag CHF (optional überschreiben)</label>
                <input class="form-control" type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount') }}">
            </div>
        </div>
        <div class="form-group">
            <label for="service_title">Leistungstitel</label>
            <input class="form-control" type="text" name="service_title" id="service_title" value="{{ old('service_title') }}" placeholder="Wird aus Produkt übernommen wenn leer">
        </div>
        <div class="form-group">
            <label for="service_detail">Leistungsdetail</label>
            <textarea class="form-control" name="service_detail" id="service_detail" rows="3">{{ old('service_detail') }}</textarea>
        </div>
        <div class="form-group">
            <label for="notes">Notiz auf Rechnung</label>
            <textarea class="form-control" name="notes" id="notes" rows="2">{{ old('notes') }}</textarea>
        </div>
        <div class="form-group">
            <label for="admin_notes">Interne Admin-Notiz</label>
            <textarea class="form-control" name="admin_notes" id="admin_notes" rows="2">{{ old('admin_notes') }}</textarea>
        </div>
        <button type="submit" class="btn">Entwurf erstellen</button>
    </form>
</div>
@endsection
