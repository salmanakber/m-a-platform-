@extends('layouts.admin')

@section('title', 'Rechnungsprodukte')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.invoices.index') }}">Rechnungen</a>
    <span>/</span>
    <span>Produkte</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Katalog</p>
            <h1>Rechnungsprodukte</h1>
            <p>Wählbare Leistungen für die Rechnungsstellung (Top-Positionen, Fachbeiträge, individuelle Positionen).</p>
        </div>
        <a class="btn btn--ghost btn--small" href="{{ route('admin.invoices.create') }}">Rechnung erstellen</a>
    </header>

    <div class="table-scroll card">
        <table class="entity-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Preis</th>
                    <th>Periode</th>
                    <th>Aktiv</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td colspan="6" style="padding:0;border:0;">
                            <form method="post" action="{{ route('admin.invoice-products.update', $product) }}" style="display:grid;grid-template-columns:120px 1.4fr 110px 120px 70px auto;gap:0.5rem;align-items:center;padding:0.65rem 0.75rem;">
                                @csrf
                                @method('PUT')
                                <code>{{ $product->sku }}</code>
                                <div>
                                    <input class="form-control" type="text" name="name" value="{{ $product->name }}" required>
                                    <input class="form-control" style="margin-top:0.35rem;" type="text" name="description" value="{{ $product->description }}" placeholder="Beschreibung">
                                </div>
                                <input class="form-control" type="number" step="0.01" min="0" name="unit_price" value="{{ $product->unit_price }}" required>
                                <select class="form-control" name="billing_period">
                                    <option value="monthly" @selected($product->billing_period === 'monthly')>Monatlich</option>
                                    <option value="one_time" @selected($product->billing_period === 'one_time')>Einmalig</option>
                                </select>
                                <label style="display:flex;gap:0.3rem;align-items:center;justify-content:center;">
                                    <input type="checkbox" name="is_active" value="1" @checked($product->is_active)>
                                </label>
                                <button class="btn btn--small" type="submit">Speichern</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="post" action="{{ route('admin.invoice-products.store') }}" class="card" style="margin-top:1rem;max-width:720px;">
        @csrf
        <h2 style="margin-top:0;font-size:1.05rem;">Neues Produkt</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="sku">SKU</label>
                <input class="form-control" name="sku" id="sku" required placeholder="promo-custom-1">
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input class="form-control" name="name" id="name" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="unit_price">Preis CHF</label>
                <input class="form-control" type="number" step="0.01" min="0" name="unit_price" id="unit_price" required>
            </div>
            <div class="form-group">
                <label for="billing_period">Periode</label>
                <select class="form-control" name="billing_period" id="billing_period">
                    <option value="monthly">Monatlich</option>
                    <option value="one_time">Einmalig</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea class="form-control" name="description" id="description" rows="2"></textarea>
        </div>
        <label style="display:flex;gap:0.35rem;align-items:center;margin-bottom:0.75rem;">
            <input type="checkbox" name="is_active" value="1" checked> Aktiv
        </label>
        <button type="submit" class="btn">Anlegen</button>
    </form>
</div>
@endsection
