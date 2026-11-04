@extends('layouts.expert')

@section('title', 'Rechnungen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Rechnungen</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Buchhaltung</p>
            <h1>Meine Rechnungen</h1>
            <p>Login erforderlich. Rechnungen werden an die hinterlegte Rechnungsadresse / E-Mail gesendet.</p>
        </div>
    </header>

    @if ($invoices->isEmpty())
        <div class="xp-empty">Noch keine Rechnungen vorhanden.</div>
    @else
        <div class="xp-panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nr.</th>
                            <th>Datum</th>
                            <th>Leistung</th>
                            <th>Betrag</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ optional($inv->invoice_date)->format('d.m.Y') }}</td>
                                <td>{{ $inv->service_title ?: '—' }}</td>
                                <td>{{ number_format((float) $inv->amount, 2, '.', "'") }} {{ $inv->currency ?: 'CHF' }}</td>
                                <td>{{ $inv->payment_status }}</td>
                                <td><a class="btn btn--small btn--ghost" href="{{ route('expert.invoices.show', $inv) }}">Öffnen</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
