@extends('layouts.admin')

@section('title', 'Promotion-Preise')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Promotion-Preise</span>
@endsection

@section('content')
<div class="admin-page" style="max-width:820px;">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Preismatrix</p>
            <h1>Promotion-Preise</h1>
            <p>CHF/Monat je Scope und Position (Excel-Matrix). Editierbar durch Admins.</p>
        </div>
    </header>

    @if (($pricesByScope ?? collect())->isEmpty())
        <div class="empty-state">
            Noch keine Preise. Bitte Seeder <code>PromotionPriceSeeder</code> ausführen (install-once mit Seed oder artisan).
        </div>
    @else
        <form method="post" action="{{ route('admin.promotion-prices.update') }}" class="card">
            @csrf
            @method('PUT')
            @foreach ($pricesByScope as $scope => $rows)
                <h2 style="font-size:1.05rem;margin:1rem 0 0.5rem;">{{ $scopeLabels[$scope] ?? $scope }}</h2>
                <div class="table-scroll">
                    <table class="entity-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>CHF / Monat</th>
                                <th>Notiz</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $price)
                                <tr>
                                    <td>
                                        Platz {{ $price->position }}
                                        <input type="hidden" name="prices[{{ $scope }}_{{ $price->id }}][id]" value="{{ $price->id }}">
                                    </td>
                                    <td>
                                        <input class="form-control" type="number" step="0.01" min="0" name="prices[{{ $scope }}_{{ $price->id }}][price_chf]" value="{{ $price->price_chf }}" required>
                                    </td>
                                    <td>
                                        <input class="form-control" type="text" name="prices[{{ $scope }}_{{ $price->id }}][note]" value="{{ $price->note }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
            <button type="submit" class="btn" style="margin-top:1rem;">Preise speichern</button>
        </form>
    @endif
</div>
@endsection
