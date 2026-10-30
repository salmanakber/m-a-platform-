@extends('layouts.admin')

@section('title', 'Experten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Experten</span>
@endsection

@section('content')
@php
    $statusLabel = [
        'pending' => 'Ausstehend',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
        'disabled' => 'Deaktiviert',
    ];
    $pill = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disabled' => 'muted',
    ];
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Verwaltung</p>
            <h1>Experten</h1>
            <p>Registrierungen und Imports prüfen — Freigabe macht Profile öffentlich sichtbar.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--small" href="{{ route('admin.experts.create') }}">Manuell anlegen</a>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.import.index') }}">Import</a>
            <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">Nur ausstehend</a>
        </div>
    </header>

    @include('partials.flash')

    @if (session('credentials_hint'))
        <div class="status-help" style="border-color:#f0c36d;background:#fff8e8;">
            <strong>Wichtig — Spam-Ordner</strong>
            <span>Bitte die Experten informieren: Wenn die Passwort-Reset-E-Mail nicht im Posteingang erscheint, den <em>Spam-/Junk-Ordner</em> prüfen. Der Link ist nur begrenzte Zeit gültig.</span>
        </div>
    @endif

    @if (session('bulk_errors') && count(session('bulk_errors')))
        <div class="alert alert--danger" style="margin-bottom:1rem;">
            <strong>Einige Einträge fehlgeschlagen:</strong>
            <ul style="margin:0.4rem 0 0;padding-left:1.1rem;">
                @foreach (session('bulk_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="status-help">
        <strong>Status erklärt</strong>
        <span>
            <b>Ausstehend</b> = neu, noch nicht geprüft ·
            <b>Freigegeben</b> = öffentlich (wenn «öffentlich sichtbar») ·
            <b>Abgelehnt</b> = abgewiesen, kann jederzeit wieder freigegeben werden ·
            <b>Deaktiviert</b> = früher freigegeben, vorübergehend ausgeschaltet — ebenfalls wieder aktivierbar.
            Profile können gelöscht werden (Soft-Delete).
        </span>
    </div>

    <div class="admin-status-chips" role="group" aria-label="Status schnell filtern">
        <a class="admin-status-chip {{ ($status ?? '') === '' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Alle</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'pending' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'pending', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Ausstehend</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'approved' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'approved', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Freigegeben</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'rejected' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'rejected', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Abgelehnt</a>
        <a class="admin-status-chip {{ ($status ?? '') === 'disabled' ? 'is-on' : '' }}" href="{{ route('admin.experts.index', array_filter(['status' => 'disabled', 'q' => $q ?: null, 'canton_id' => $cantonId ?: null])) }}">Deaktiviert</a>
    </div>

    <form method="get" class="admin-filterbar" novalidate>
        <div class="admin-filterbar__fields">
            <div class="form-group">
                <label for="filter_q">Suche</label>
                <input class="form-control" type="search" name="q" id="filter_q" value="{{ $q ?? '' }}" placeholder="Firma, E-Mail, Kontakt…">
            </div>
            <div class="form-group">
                <label for="filter_status">Status</label>
                <select class="form-control" name="status" id="filter_status">
                    <option value="">Alle</option>
                    <option value="pending" @selected(($status ?? '') === 'pending')>Ausstehend</option>
                    <option value="approved" @selected(($status ?? '') === 'approved')>Freigegeben</option>
                    <option value="rejected" @selected(($status ?? '') === 'rejected')>Abgelehnt</option>
                    <option value="disabled" @selected(($status ?? '') === 'disabled')>Deaktiviert</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_canton_id">Kanton</label>
                <select class="form-control" name="canton_id" id="filter_canton_id">
                    <option value="">Alle</option>
                    @foreach ($cantons as $canton)
                        <option value="{{ $canton->id }}" @selected((string) ($cantonId ?? '') === (string) $canton->id)>{{ $canton->name_de }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="filter_gruppe">Fachgruppe</label>
                <select class="form-control" name="gruppe" id="filter_gruppe">
                    <option value="">Alle</option>
                    @foreach (($professionalGroups ?? []) as $groupKey => $groupLabel)
                        <option value="{{ $groupKey }}" @selected(($group ?? '') === $groupKey)>{{ $groupLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="admin-filterbar__actions">
            <button type="submit" class="btn btn--small">Filtern</button>
            @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($cantonId ?? '') !== '' || ($group ?? '') !== '')
                <a class="admin-filterbar__reset" href="{{ route('admin.experts.index') }}">Zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($experts->isEmpty())
        <div class="admin-card">
            <div class="empty-state empty-state--soft">
                <p><strong>Keine Experten gefunden.</strong></p>
                <p>Passen Sie die Filter an oder prüfen Sie ausstehende Registrierungen.</p>
            </div>
        </div>
    @else
        <form method="post" action="{{ route('admin.experts.bulk') }}" id="expertsBulkForm" class="admin-bulk-form" novalidate>
            @csrf
            <div class="admin-card__head admin-card__head--row">
                <div>
                    <h2>{{ $experts->total() }} Ergebnisse</h2>
                    <p>Seite {{ $experts->currentPage() }} von {{ $experts->lastPage() }}</p>
                </div>
                <div class="bulk-bar">
                    <select class="form-control" name="action" id="bulkAction">
                        <option value="">Bulk-Aktion…</option>
                        <option value="approve">Freigeben / wiederherstellen</option>
                        <option value="reject">Ablehnen</option>
                        <option value="deactivate">Deaktivieren</option>
                        <option value="delete">Löschen</option>
                        <option value="set_password">Passwort setzen</option>
                        <option value="send_password_reset">Passwort-Reset-Link senden</option>
                    </select>
                    <button type="submit" class="btn btn--small" id="bulkSubmit">Ausführen</button>
                </div>
            </div>

            <div class="admin-bulk-panel" id="bulkPasswordPanel" hidden>
                <strong>Neues Passwort für alle Ausgewählten</strong>
                <p>Für jeden ausgewählten Experten wird ein persönliches Login angelegt/aktualisiert und dieses Passwort gesetzt (mind. 8 Zeichen). Teilen Sie das Passwort sicher mit den Experten — oder nutzen Sie besser «Passwort-Reset-Link senden».</p>
                <div class="form-row" style="margin-top:0.65rem;">
                    <div class="form-group">
                        <label for="bulk_password">Passwort</label>
                        <input class="form-control" type="password" name="bulk_password" id="bulk_password" minlength="8" autocomplete="new-password">
                        @error('bulk_password')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="bulk_password_confirmation">Passwort bestätigen</label>
                        <input class="form-control" type="password" name="bulk_password_confirmation" id="bulk_password_confirmation" minlength="8" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="admin-bulk-panel admin-bulk-panel--warn" id="bulkResetPanel" hidden>
                <strong>Passwort-Reset-Links senden</strong>
                <p>
                    Das System sendet jedem ausgewählten Experten einen frischen Reset-Link an die Profil-E-Mail
                    und legt bei Bedarf ein persönliches Login an.
                </p>
                <ul class="admin-bulk-panel__list">
                    <li>Bitten Sie die Experten, auch den <strong>Spam-/Junk-Ordner</strong> zu prüfen, falls die Mail nicht im Posteingang ist.</li>
                    <li>Der Link ist nur begrenzte Zeit gültig.</li>
                    <li>Große Mengen können einige Sekunden dauern — bitte nicht doppelt klicken.</li>
                </ul>
                <label class="admin-bulk-panel__confirm">
                    <input type="checkbox" name="confirm_reset_spam_notice" id="confirm_reset_spam_notice" value="1">
                    <span>Ich habe die Anweisung verstanden und werde die Experten auf den Spam-Ordner hinweisen.</span>
                </label>
                @error('confirm_reset_spam_notice')<div class="field-error">{{ $message }}</div>@enderror
            </div>
        </form>

        <div class="admin-card admin-card--flush-top">
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" id="checkAll" form="expertsBulkForm" aria-label="Alle auswählen" title="Alle auf dieser Seite">
                            </th>
                            <th>Experte</th>
                            <th>Kontakt</th>
                            <th>Gruppe</th>
                            <th>Anfragen</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($experts as $expert)
                            @php $office = $expert->offices->firstWhere('is_primary', true) ?? $expert->offices->first(); @endphp
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $expert->id }}" class="row-check" form="expertsBulkForm">
                                </td>
                                <td>
                                    <div class="entity-primary">
                                        <div class="entity-avatar">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 1)) }}</div>
                                        <div>
                                            <p class="entity-name">{{ $expert->company_name }}</p>
                                            <p class="entity-sub">
                                                @if ($office)
                                                    {{ $office->city }}@if($office->canton) · {{ $office->canton->code }}@endif
                                                @else
                                                    —
                                                @endif
                                                @if ($expert->is_public) · öffentlich @endif
                                                @if ($expert->imported_from) · Import @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $expert->email }}</div>
                                    <div class="entity-sub">{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="entity-sub">{{ ($professionalGroups ?? [])[$expert->professional_group] ?? ($expert->professional_group ?: '—') }}</span>
                                    <div class="chip-row" style="justify-content:flex-start;margin-top:0.25rem;">
                                        @if ($expert->offers_buy)<span class="badge badge--buy">Kauf</span>@endif
                                        @if ($expert->offers_sell)<span class="badge badge--sell">Verkauf</span>@endif
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.experts.show', $expert) }}#anfragen">{{ (int) ($expert->leads_count ?? 0) }}</a>
                                </td>
                                <td>
                                    <span class="pill pill--{{ $pill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $expert) }}">Öffnen</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $experts->links() }}</div>
        </div>
    @endif
</div>

<style>
.admin-bulk-panel {
    margin: 0 0 0.85rem;
    padding: 1rem 1.15rem;
    border: 1px solid #d7e3ec;
    border-radius: 10px;
    background: #f7fafc;
}
.admin-bulk-panel--warn {
    border-color: #f0c36d;
    background: #fff8e8;
}
.admin-bulk-panel strong { display: block; margin-bottom: 0.35rem; }
.admin-bulk-panel p { margin: 0; color: var(--ink-soft, #4a5560); font-size: 0.92rem; line-height: 1.5; }
.admin-bulk-panel__list {
    margin: 0.65rem 0 0.75rem;
    padding-left: 1.15rem;
    color: var(--ink-soft, #4a5560);
    font-size: 0.9rem;
    line-height: 1.55;
}
.admin-bulk-panel__confirm {
    display: flex;
    gap: 0.55rem;
    align-items: flex-start;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
}
.admin-bulk-panel__confirm input { margin-top: 0.2rem; }
</style>
<script>
(function () {
    var bulkForm = document.getElementById('expertsBulkForm');
    var all = document.getElementById('checkAll');
    var actionEl = document.getElementById('bulkAction');
    var passwordPanel = document.getElementById('bulkPasswordPanel');
    var resetPanel = document.getElementById('bulkResetPanel');
    var labels = {
        approve: 'freigeben',
        reject: 'ablehnen',
        deactivate: 'deaktivieren',
        delete: 'löschen',
        set_password: 'Passwort setzen',
        send_password_reset: 'Passwort-Reset-Links senden'
    };

    function syncPanels() {
        var action = actionEl ? actionEl.value : '';
        if (passwordPanel) passwordPanel.hidden = action !== 'set_password';
        if (resetPanel) resetPanel.hidden = action !== 'send_password_reset';
    }

    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (el) { el.checked = all.checked; });
        });
    }
    if (actionEl) {
        actionEl.addEventListener('change', syncPanels);
        syncPanels();
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var action = actionEl ? actionEl.value : '';
            var checked = document.querySelectorAll('.row-check:checked').length;
            if (!action) { e.preventDefault(); alert('Bitte eine Bulk-Aktion wählen.'); return; }
            if (!checked) { e.preventDefault(); alert('Bitte mindestens einen Experten auswählen.'); return; }

            if (action === 'set_password') {
                var pw = document.getElementById('bulk_password');
                var pw2 = document.getElementById('bulk_password_confirmation');
                if (!pw || !pw.value || pw.value.length < 8) {
                    e.preventDefault();
                    alert('Bitte ein Passwort mit mindestens 8 Zeichen eingeben.');
                    return;
                }
                if (pw2 && pw.value !== pw2.value) {
                    e.preventDefault();
                    alert('Passwort und Bestätigung stimmen nicht überein.');
                    return;
                }
            }

            if (action === 'send_password_reset') {
                var confirmBox = document.getElementById('confirm_reset_spam_notice');
                if (!confirmBox || !confirmBox.checked) {
                    e.preventDefault();
                    alert('Bitte bestätigen Sie den Hinweis zum Spam-Ordner.');
                    return;
                }
                var msg = checked + ' Passwort-Reset-Link(s) senden?\n\n'
                    + 'Wichtig: Bitte die Experten darauf hinweisen, auch den Spam-/Junk-Ordner zu prüfen, '
                    + 'falls die E-Mail nicht im Posteingang erscheint.';
                if (!confirm(msg)) e.preventDefault();
                return;
            }

            if (!confirm(checked + ' Experte(n) wirklich «' + (labels[action] || action) + '»?')) e.preventDefault();
        });
    }
})();
</script>
@endsection
