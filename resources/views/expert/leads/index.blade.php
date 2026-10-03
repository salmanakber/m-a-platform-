@extends('layouts.expert')

@section('title', 'Anfragen')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Anfragen</span>
@endsection

@section('content')
@php
    $leadLabel = [
        'new' => 'Neu',
        'contacted' => 'Kontaktiert',
        'in_progress' => 'In Bearbeitung',
        'closed' => 'Abgeschlossen',
    ];
    $leadPill = [
        'new' => 'warning',
        'contacted' => 'info',
        'in_progress' => 'success',
        'closed' => 'muted',
    ];
@endphp

<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Pipeline</p>
            <h1>Anfragen</h1>
            <p>Kontaktanfragen aus dem öffentlichen Verzeichnis — vertraulich und direkt an Sie.</p>
        </div>
    </header>

    <section class="xp-panel">
        @if ($leads->isEmpty())
            <div class="xp-empty">
                <strong>Noch keine Kontaktanfragen</strong>
                <p>Sobald Interessenten Sie kontaktieren, erscheinen sie hier.</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Interessent</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                            <tr>
                                <td>{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <strong>{{ $lead->owner_name }}</strong>
                                    <div class="entity-sub">{{ $lead->email }}@if($lead->company_name) · {{ $lead->company_name }}@endif</div>
                                </td>
                                <td><span class="pill pill--{{ $leadPill[$lead->status] ?? 'muted' }}">{{ $leadLabel[$lead->status] ?? $lead->status }}</span></td>
                                <td><a class="btn btn--ghost btn--small" href="{{ route('expert.leads.show', $lead) }}">Details</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $leads->links() }}</div>
        @endif
    </section>
</div>
@endsection
