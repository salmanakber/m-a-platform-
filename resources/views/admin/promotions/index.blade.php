@extends('layouts.admin')

@section('title', 'Promotionen')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> · Promotionen
@endsection

@section('content')
@php
    $statusLabel = ['pending' => 'Ausstehend', 'active' => 'Aktiv', 'expired' => 'Abgelaufen', 'cancelled' => 'Storniert'];
    $pill = ['pending' => 'warning', 'active' => 'success', 'expired' => 'muted', 'cancelled' => 'danger'];
@endphp

<div class="admin-page-hero">
    <div>
        <p class="page-kicker">Monetarisierung</p>
        <h1>Promotionen</h1>
        <p>Manuelle Freischaltung nach Rechnungszahlung — max. 3 Positionen pro Kanton.</p>
    </div>
</div>

<form method="get" class="admin-toolbar sheet">
    <div class="admin-search">
        <select class="form-control" name="status">
            <option value="">Alle Status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--small">Filtern</button>
    </div>
</form>

@if ($promotions->isEmpty())
    <div class="sheet"><div class="empty-state" style="border:none;margin:0;">Keine Promotionen.</div></div>
@else
    <div class="sheet">
        <div class="sheet__head"><h2>{{ $promotions->total() }} Einträge</h2></div>
        <table class="entity-table">
            <thead><tr><th>Experte</th><th>Kanton / Pos.</th><th>Status</th><th>Rechnung</th><th></th></tr></thead>
            <tbody>
                @foreach ($promotions as $promotion)
                    <tr>
                        <td>
                            <div class="entity-primary">
                                <div class="entity-avatar">{{ mb_strtoupper(mb_substr($promotion->expert->company_name ?? '?', 0, 1)) }}</div>
                                <div>
                                    <p class="entity-name">{{ $promotion->expert->company_name ?? '—' }}</p>
                                    <p class="entity-sub">{{ optional($promotion->starts_at)->format('d.m.Y') }} – {{ optional($promotion->ends_at)->format('d.m.Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $promotion->canton->name_de ?? '—' }} · Pos. {{ $promotion->position_number }}</td>
                        <td><span class="pill pill--{{ $pill[$promotion->status] ?? 'muted' }}">{{ $statusLabel[$promotion->status] ?? $promotion->status }}</span></td>
                        <td>{{ $promotion->invoice->payment_status ?? '—' }}</td>
                        <td>
                            <div class="row-actions">
                                @if ($promotion->status !== 'active')
                                    <form action="{{ route('admin.promotions.activate', $promotion) }}" method="post">@csrf<button type="submit" class="btn btn--small">Aktivieren</button></form>
                                @endif
                                @if ($promotion->status === 'active')
                                    <form action="{{ route('admin.promotions.expire', $promotion) }}" method="post">@csrf<button type="submit" class="btn btn--ghost btn--small">Beenden</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sheet__footer">{{ $promotions->links() }}</div>
    </div>
@endif
@endsection
