@extends('layouts.admin')

@section('title', 'Promotionen')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Promotionen</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'active' => 'Aktiv',
        'expired' => 'Abgelaufen',
        'cancelled' => 'Storniert',
    ];
    $pill = [
        'pending' => 'warning',
        'active' => 'success',
        'expired' => 'muted',
        'cancelled' => 'danger',
    ];
    $payLabel = [
        'pending' => 'Offen',
        'paid' => 'Bezahlt',
        'cancelled' => 'Storniert',
        'overdue' => 'Überfällig',
    ];
    $chipBase = array_filter([
        'q' => ($q ?? '') !== '' ? $q : null,
        'canton_id' => ($cantonId ?? '') !== '' ? $cantonId : null,
    ]);
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Monetarisierung</p>
            <h1>Promotionen</h1>
            <p>Kantonale Top-Plätze freischalten, Rechnung erstellen, stornieren oder beenden.</p>
        </div>
        <div class="admin-page-hero__actions">
            <span class="admin-price-chip">Monatspreis <strong>{{ number_format((float) ($monthlyPrice ?? 0), 2, '.', "'") }} CHF</strong></span>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.settings.edit') }}">Preis ändern</a>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.invoices.index') }}">Rechnungen</a>
        </div>
    </header>

    @include('partials.flash')

    <div class="status-help">
        <strong>Ablauf</strong>
        <span>Experte fragt Slot an → Status <em>Ausstehend</em> → Admin erstellt Rechnung → Zahlung markieren → Aktivieren. «Freie Slots» = freie Plätze, nicht kostenlos.</span>
    </div>

    <div class="admin-status-chips" role="group" aria-label="Status schnell filtern">
        <a class="admin-status-chip {{ ($status ?? '') === '' ? 'is-on' : '' }}" href="{{ route('admin.promotions.index', $chipBase) }}">Alle</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'pending' ? 'is-on' : '' }}" href="{{ route('admin.promotions.index', $chipBase + ['status' => 'pending']) }}">Ausstehend</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'active' ? 'is-on' : '' }}" href="{{ route('admin.promotions.index', $chipBase + ['status' => 'active']) }}">Aktiv</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'expired' ? 'is-on' : '' }}" href="{{ route('admin.promotions.index', $chipBase + ['status' => 'expired']) }}">Abgelaufen</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'cancelled' ? 'is-on' : '' }}" href="{{ route('admin.promotions.index', $chipBase + ['status' => 'cancelled']) }}">Storniert</a>
    </div>

    <form method="get" class="admin-filterbar" id="promoFilterForm">
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="q">Suche</label>
                <input class="form-control" type="search" name="q" id="q" value="{{ $q ?? '' }}" placeholder="Firma oder E-Mail…">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" name="status" id="status">
                    <option value="">Alle</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="canton_id">Kanton</label>
                <select class="form-control" name="canton_id" id="canton_id">
                    <option value="">Alle</option>
                    @foreach ($cantons as $canton)
                        <option value="{{ $canton->id }}" @selected((string) ($cantonId ?? '') === (string) $canton->id)>{{ $canton->code }} — {{ $canton->name_de }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($cantonId ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.promotions.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($promotions->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">
                <p><strong>Keine Promotionen.</strong></p>
                <p>Sobald Experten einen Slot anfragen, erscheinen sie hier.</p>
            </div>
        </div>
    @else
        {{-- Bulk form is separate (no nesting). Checkboxes use form="…". --}}
        <form method="post" action="{{ route('admin.promotions.bulk') }}" id="promotionsBulkForm" class="admin-bulk-form">
            @csrf
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $promotions->total() }} Einträge</h2>
                    <p>Seite {{ $promotions->currentPage() }} von {{ max(1, $promotions->lastPage()) }}</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="action" id="bulkAction">
                        <option value="">Bulk-Aktion…</option>
                        <option value="activate">Aktivieren</option>
                        <option value="expire">Beenden</option>
                        <option value="cancel">Stornieren</option>
                    </select>
                    <button type="submit" class="btn btn--small" id="bulkSubmit">Ausführen</button>
                </div>
            </div>
        </form>

        <div class="admin-card admin-card--flush-top">
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="checkAll" form="promotionsBulkForm" aria-label="Alle auswählen"></th>
                            <th>Experte</th>
                            <th>Kanton / Pos.</th>
                            <th>Status</th>
                            <th>Rechnung / Betrag</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promotions as $promotion)
                            @php $invoice = $promotion->invoice; @endphp
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $promotion->id }}" class="row-check" form="promotionsBulkForm">
                                </td>
                                <td>
                                    <div class="entity-primary">
                                        <div class="entity-avatar">{{ mb_strtoupper(mb_substr($promotion->expert->company_name ?? '?', 0, 1)) }}</div>
                                        <div>
                                            <p class="entity-name">{{ $promotion->expert->company_name ?? '—' }}</p>
                                            <p class="entity-sub">
                                                @if ($promotion->starts_at)
                                                    {{ $promotion->starts_at->format('d.m.Y') }} – {{ optional($promotion->ends_at)->format('d.m.Y') }}
                                                @else
                                                    Noch nicht gestartet
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $promotion->canton->code ?? '—' }}</strong>
                                    <div class="entity-sub">{{ $promotion->canton->name_de ?? '' }} · Pos. {{ $promotion->position_number }}</div>
                                </td>
                                <td>
                                    <span class="pill pill--{{ $pill[$promotion->status] ?? 'muted' }}">{{ $statusLabel[$promotion->status] ?? $promotion->status }}</span>
                                </td>
                                <td>
                                    @if ($invoice)
                                        <p class="entity-name">{{ number_format((float) $invoice->amount, 2, '.', "'") }} {{ $invoice->currency }}</p>
                                        <p class="entity-sub">
                                            {{ $invoice->invoice_number }}
                                            · {{ $payLabel[$invoice->payment_status] ?? $invoice->payment_status }}
                                        </p>
                                    @else
                                        <p class="entity-name" style="color:var(--stone);">Keine Rechnung</p>
                                        <p class="entity-sub">Vorlage: {{ number_format((float) ($monthlyPrice ?? 0), 2, '.', "'") }} CHF</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions row-actions--stack">
                                        @if (! $invoice && $promotion->status === 'pending')
                                            <form action="{{ route('admin.promotions.create-invoice', $promotion) }}" method="post">
                                                @csrf
                                                <button type="submit" class="btn btn--small">Rechnung</button>
                                            </form>
                                        @endif
                                        @if ($promotion->status !== 'active' && $promotion->status !== 'cancelled')
                                            <form action="{{ route('admin.promotions.activate', $promotion) }}" method="post">
                                                @csrf
                                                <button type="submit" class="btn btn--small">Aktivieren</button>
                                            </form>
                                        @endif
                                        @if ($promotion->status === 'active')
                                            <form action="{{ route('admin.promotions.expire', $promotion) }}" method="post">
                                                @csrf
                                                <button type="submit" class="btn btn--ghost btn--small">Beenden</button>
                                            </form>
                                        @endif
                                        @if (! in_array($promotion->status, ['cancelled', 'expired'], true))
                                            <form action="{{ route('admin.promotions.cancel', $promotion) }}" method="post" onsubmit="return confirm('Promotion wirklich stornieren?');">
                                                @csrf
                                                <button type="submit" class="btn btn--ghost btn--small">Stornieren</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot admin-pager-footer">
                <p class="admin-pager-meta">{{ $promotions->firstItem() }}–{{ $promotions->lastItem() }} von {{ $promotions->total() }}</p>
                {{ $promotions->links() }}
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    var bulkForm = document.getElementById('promotionsBulkForm');
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
            if (!action) {
                e.preventDefault();
                alert('Bitte eine Bulk-Aktion wählen.');
                return;
            }
            if (!checked) {
                e.preventDefault();
                alert('Bitte mindestens eine Promotion auswählen.');
                return;
            }
            if (!confirm(checked + ' Promotion(en) wirklich ausführen?')) {
                e.preventDefault();
            }
        });
    }
})();
</script>
@endsection
