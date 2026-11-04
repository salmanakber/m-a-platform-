@extends('layouts.expert')

@section('title', 'Rechnung '.$invoice->invoice_number)

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.invoices.index') }}">Rechnungen</a>
    <span>/</span>
    <span>{{ $invoice->invoice_number }}</span>
@endsection

@section('content')
@php
    $statusLabels = [
        'pending' => 'Offen',
        'paid' => 'Bezahlt',
        'overdue' => 'Überfällig',
        'cancelled' => 'Storniert',
    ];
@endphp
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Rechnung</p>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>
                {{ optional($invoice->invoice_date)->format('d.m.Y') }}
                · {{ $statusLabels[$invoice->payment_status] ?? $invoice->payment_status }}
                · {{ number_format((float) $invoice->amount, 2, '.', "'") }} {{ $invoice->currency ?: 'CHF' }}
            </p>
        </div>
        <div class="xp-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('expert.invoices.index') }}">Alle Rechnungen</a>
        </div>
    </header>

    <div class="xp-panel">
        <div class="xp-panel__head">
            <div>
                <h2>{{ $invoice->service_title ?: 'Leistung' }}</h2>
                <p>{{ $invoice->service_detail }}</p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Zeitraum</label>
                <p>
                    @if ($invoice->period_start || $invoice->period_end)
                        {{ optional($invoice->period_start)->format('d.m.Y') ?: '—' }}
                        –
                        {{ optional($invoice->period_end)->format('d.m.Y') ?: '—' }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="form-group">
                <label>Menge × Preis</label>
                <p>
                    {{ rtrim(rtrim(number_format((float) ($invoice->quantity ?: 1), 2, '.', "'"), '0'), '.') }}
                    ×
                    {{ number_format((float) ($invoice->unit_price ?? $invoice->amount), 2, '.', "'") }} CHF
                </p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Zwischensumme</label>
                <p>{{ number_format($invoice->netAmount(), 2, '.', "'") }} CHF</p>
            </div>
            <div class="form-group">
                <label>MwSt. @if($invoice->vat_rate)({{ number_format((float) $invoice->vat_rate, 1, '.', "'") }} %)@endif</label>
                <p>{{ number_format($invoice->vatAmount(), 2, '.', "'") }} CHF</p>
            </div>
            <div class="form-group">
                <label>Total</label>
                <p><strong>{{ number_format($invoice->grossAmount(), 2, '.', "'") }} {{ $invoice->currency ?: 'CHF' }}</strong></p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Rechnungsempfänger</label>
                <p>
                    {{ $invoice->billing_name ?: $expert->company_name }}<br>
                    @if ($invoice->billing_address_line || $expert->billing_address_line)
                        {{ $invoice->billing_address_line ?: $expert->billing_address_line }}<br>
                    @endif
                    {{ trim(($invoice->billing_postal_code ?: $expert->billing_postal_code).' '.($invoice->billing_city ?: $expert->billing_city)) }}<br>
                    {{ $invoice->billing_email ?: ($expert->billing_email ?: $expert->email) }}
                </p>
            </div>
            <div class="form-group">
                <label>Zahlungsinfo</label>
                <p>
                    @if (!empty($sender['bank_name']))Bank: {{ $sender['bank_name'] }}<br>@endif
                    @if (!empty($sender['bank_iban']))IBAN: {{ $sender['bank_iban'] }}<br>@endif
                    @if (!empty($sender['bank_bic']))BIC: {{ $sender['bank_bic'] }}<br>@endif
                    @if (!empty($sender['payment_hint'])){{ $sender['payment_hint'] }}@endif
                    @if (empty($sender['bank_iban']) && empty($sender['payment_hint']))
                        —
                    @endif
                </p>
            </div>
        </div>

        @if ($invoice->notes)
            <div class="form-group">
                <label>Bemerkung</label>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
