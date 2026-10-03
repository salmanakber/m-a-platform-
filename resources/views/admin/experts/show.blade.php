@extends('layouts.admin')

@section('title', $expert->company_name)
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> ·
    <a href="{{ route('admin.experts.index') }}">Experten</a> · Detail
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

<a href="{{ route('admin.experts.index') }}" class="back-link">← Zurück zur Liste</a>

<div class="detail-hero">
    <div>
        <span class="pill pill--{{ $pill[$expert->status] ?? 'muted' }}">{{ $statusLabel[$expert->status] ?? $expert->status }}</span>
        <h1>{{ $expert->company_name }}</h1>
        <p>
            {{ $expert->email }}
            @if ($expert->phone) · {{ $expert->phone }}@endif
            · slug <code style="color:rgba(255,255,255,0.75);">{{ $expert->slug }}</code>
        </p>
    </div>
    <div class="detail-hero__actions">
        <a href="{{ route('admin.experts.edit', $expert) }}" class="btn btn--small">Bearbeiten</a>
        @if ($expert->status === 'pending')
            <form action="{{ route('admin.experts.approve', $expert) }}" method="post">@csrf<button type="submit" class="btn btn--small">Freigeben</button></form>
            <form action="{{ route('admin.experts.reject', $expert) }}" method="post" onsubmit="return confirm('Experte wirklich ablehnen?');">
                @csrf
                <input type="hidden" name="rejection_reason" value="Profil unvollständig oder nicht passend.">
                <button type="submit" class="btn btn--ghost btn--small">Ablehnen</button>
            </form>
        @endif
        @if ($expert->status !== 'disabled')
            <form action="{{ route('admin.experts.deactivate', $expert) }}" method="post">@csrf<button type="submit" class="btn btn--ghost btn--small">Deaktivieren</button></form>
        @else
            <form action="{{ route('admin.experts.activate', $expert) }}" method="post">@csrf<button type="submit" class="btn btn--small">Aktivieren</button></form>
        @endif
        @if ($expert->is_public && $expert->status === 'approved')
            <a href="{{ route('experts.show', $expert->slug) }}" class="btn btn--ghost btn--small" target="_blank" rel="noopener">Öffentliches Profil</a>
        @endif
    </div>
</div>

<div class="detail-layout">
    <div class="side-stack">
        <section class="sheet">
            <div class="sheet__head"><h2>Stammdaten</h2></div>
            <dl class="meta-list">
                <div class="meta-list__row"><dt>E-Mail</dt><dd><a href="mailto:{{ $expert->email }}">{{ $expert->email }}</a></dd></div>
                <div class="meta-list__row"><dt>Telefon</dt><dd>{{ $expert->phone ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Website</dt><dd>@if($expert->website)<a href="{{ $expert->website }}" target="_blank" rel="noopener">{{ $expert->website }}</a>@else — @endif</dd></div>
                <div class="meta-list__row"><dt>Ansprechpartner</dt><dd>{{ trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')) ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Fokus</dt><dd>{{ collect([($expert->offers_buy ? 'Kauf' : null), ($expert->offers_sell ? 'Verkauf' : null)])->filter()->implode(' · ') ?: '—' }}</dd></div>
                <div class="meta-list__row"><dt>Öffentlich</dt><dd>{{ $expert->is_public ? 'Ja' : 'Nein' }}</dd></div>
                <div class="meta-list__row"><dt>Crawl</dt><dd>{{ $expert->crawl_enabled ? 'Aktiv' : 'Aus' }}@if($expert->website_crawl_url) · {{ $expert->website_crawl_url }}@endif</dd></div>
                <div class="meta-list__row"><dt>Registriert</dt><dd>{{ optional($expert->created_at)->format('d.m.Y H:i') }}</dd></div>
            </dl>
        </section>

        @if ($expert->description || $expert->services_text)
            <section class="sheet">
                <div class="sheet__head"><h2>Profiltext</h2></div>
                <div style="padding:1.25rem 1.35rem;">
                    @if ($expert->description)
                        <p style="margin:0 0 1rem;color:var(--ink-soft);line-height:1.65;">{{ $expert->description }}</p>
                    @endif
                    @if ($expert->services_text)
                        <p style="margin:0;color:var(--stone);font-size:0.92rem;"><strong>Leistungen:</strong> {{ $expert->services_text }}</p>
                    @endif
                </div>
            </section>
        @endif

        @if ($expert->rejection_reason)
            <section class="sheet">
                <div class="sheet__head"><h2>Ablehnungsgrund</h2></div>
                <p class="message-block">{{ $expert->rejection_reason }}</p>
            </section>
        @endif
    </div>

    <aside class="side-stack">
        <section class="sheet">
            <div class="sheet__head"><h2>Standorte</h2></div>
            @if ($expert->offices->isEmpty())
                <div class="empty-state" style="border:none;margin:0;">Keine Standorte.</div>
            @else
                <div style="padding:0.5rem 0;">
                    @foreach ($expert->offices as $office)
                        <div style="padding:0.95rem 1.25rem;border-bottom:1px solid #eef1ee;">
                            <strong style="display:block;margin-bottom:0.2rem;">{{ $office->is_primary ? 'Hauptsitz' : ($office->label ?: 'Standort') }}</strong>
                            <span style="color:var(--stone);font-size:0.9rem;">
                                {{ $office->address_line }}<br>
                                {{ $office->postal_code }} {{ $office->city }}
                                @if ($office->canton) · {{ $office->canton->name_de }}@endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="sheet">
            <div class="sheet__head"><h2>Schnellaktionen</h2></div>
            <div style="padding:1rem 1.25rem;display:grid;gap:0.5rem;">
                <a class="btn btn--block btn--ghost btn--small" href="{{ route('admin.experts.edit', $expert) }}">Alle Felder bearbeiten</a>
                <a class="btn btn--block btn--ghost btn--small" href="{{ route('admin.leads.index', ['expert_id' => $expert->id]) }}">Anfragen dieses Experten</a>
            </div>
        </section>
    </aside>
</div>
@endsection
