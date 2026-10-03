@extends('layouts.admin')

@section('title', 'Anfragen')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a> · Anfragen
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

<div class="admin-page-hero">
    <div>
        <p class="page-kicker">Lead-Management</p>
        <h1>Anfragen</h1>
        <p>Alle Kontaktanfragen an Experten — filtern, öffnen und Status setzen.</p>
    </div>
</div>

<form method="get" class="admin-toolbar sheet">
    <div class="admin-search">
        <div class="form-group" style="margin:0;">
            <label for="status">Status</label>
            <select class="form-control" name="status" id="status">
                <option value="">Alle</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="expert_id">Experte</label>
            <select class="form-control" name="expert_id" id="expert_id">
                <option value="">Alle</option>
                @foreach ($experts as $ex)
                    <option value="{{ $ex->id }}" @selected((string) ($expertId ?? '') === (string) $ex->id)>{{ $ex->company_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="date_from">Von</label>
            <input class="form-control" type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}">
        </div>
        <div class="form-group" style="margin:0;">
            <label for="date_to">Bis</label>
            <input class="form-control" type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}">
        </div>
        <button type="submit" class="btn btn--small">Filtern</button>
    </div>
</form>

@if ($leads->isEmpty())
    <div class="sheet"><div class="empty-state" style="border:none;margin:0;">Keine Anfragen für diese Filter.</div></div>
@else
    <div class="sheet">
        <div class="sheet__head">
            <h2>{{ $leads->total() }} Anfragen</h2>
        </div>
        <table class="entity-table">
            <thead>
                <tr>
                    <th>Interessent</th>
                    <th>Experte</th>
                    <th>Datum</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($leads as $lead)
                    <tr>
                        <td>
                            <div class="entity-primary">
                                <div class="entity-avatar">{{ mb_strtoupper(mb_substr($lead->owner_name, 0, 1)) }}</div>
                                <div>
                                    <p class="entity-name">{{ $lead->owner_name }}</p>
                                    <p class="entity-sub">{{ $lead->email }}@if($lead->phone) · {{ $lead->phone }}@endif</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $lead->expert->company_name ?? '—' }}</td>
                        <td>
                            <div>{{ $lead->created_at->format('d.m.Y') }}</div>
                            <div class="entity-sub">{{ $lead->created_at->format('H:i') }}</div>
                        </td>
                        <td><span class="pill pill--{{ $pill[$lead->status] ?? 'muted' }}">{{ $statusLabel[$lead->status] ?? $lead->status }}</span></td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn--small" href="{{ route('admin.leads.show', $lead) }}">Öffnen</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sheet__footer">{{ $leads->links() }}</div>
    </div>
@endif
@endsection
