@extends('layouts.admin')

@section('title', 'Anfrage #'.$lead->id)
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> ·
    <a href="{{ route('admin.leads.index') }}">Anfragen</a> · #{{ $lead->id }}
@endsection

@section('content')
@php
    $statusLabel = [
        'new' => 'Neu',
        'contacted' => 'Kontaktiert',
        'in_progress' => 'In Bearbeitung',
        'closed' => 'Geschlossen',
    ];
    $pill = [
        'new' => 'info',
        'contacted' => 'warning',
        'in_progress' => 'warning',
        'closed' => 'muted',
    ];
@endphp

<a href="{{ route('admin.leads.index') }}" class="back-link">← Zurück zu Anfragen</a>

<div class="detail-hero">
    <div>
        <span class="pill pill--{{ $pill[$lead->status] ?? 'muted' }}">{{ $statusLabel[$lead->status] ?? $lead->status }}</span>
        <h1>{{ $lead->owner_name }}</h1>
        <p>
            Anfrage an {{ $lead->expert->company_name ?? '—' }}
            · {{ $lead->created_at->format('d.m.Y H:i') }}
        </p>
    </div>
    <div class="detail-hero__actions">
        <a class="btn btn--ghost btn--small" href="mailto:{{ $lead->email }}">E-Mail schreiben</a>
        @if ($lead->expert)
            <a class="btn btn--ghost btn--small" href="{{ route('admin.experts.show', $lead->expert) }}">Experte öffnen</a>
        @endif
    </div>
</div>

<div class="detail-layout">
    <div class="side-stack">
        <section class="sheet">
            <div class="sheet__head"><h2>Kontaktdaten</h2></div>
            <dl class="meta-list">
                <div class="meta-list__row"><dt>Name</dt><dd>{{ $lead->owner_name }}</dd></div>
                <div class="meta-list__row"><dt>E-Mail</dt><dd><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></dd></div>
                <div class="meta-list__row"><dt>Telefon</dt><dd>{{ $lead->phone ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Unternehmen</dt><dd>{{ $lead->company_name ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Branche</dt><dd>{{ $lead->industry ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Kanton</dt><dd>{{ $lead->canton->name_de ?? '—' }}</dd></div>
                <div class="meta-list__row"><dt>Kontext</dt><dd>{{ $lead->buy_sell_context ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Experte</dt><dd>{{ $lead->expert->company_name ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="sheet">
            <div class="sheet__head"><h2>Nachricht</h2></div>
            @if ($lead->message)
                <p class="message-block">{{ $lead->message }}</p>
            @else
                <div class="empty-state" style="border:none;margin:0;">Keine Nachricht hinterlegt.</div>
            @endif
        </section>
    </div>

    <aside class="side-stack">
        <section class="sheet">
            <div class="sheet__head"><h2>Status ändern</h2></div>
            <form method="post" action="{{ route('admin.leads.update-status', $lead) }}" style="padding:1.15rem 1.25rem;">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" name="status" id="status">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected($lead->status === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn--block">Aktualisieren</button>
            </form>
        </section>

        <section class="sheet">
            <div class="sheet__head"><h2>Benachrichtigungen</h2></div>
            <dl class="meta-list">
                <div class="meta-list__row"><dt>Kunde</dt><dd>{{ $lead->customer_notified_at ? $lead->customer_notified_at->format('d.m.Y H:i') : '—' }}</dd></div>
                <div class="meta-list__row"><dt>Experte</dt><dd>{{ $lead->expert_notified_at ? $lead->expert_notified_at->format('d.m.Y H:i') : '—' }}</dd></div>
            </dl>
        </section>
    </aside>
</div>
@endsection
