@extends('layouts.expert')

@section('title', 'Anfrage')

@section('content')
    <a href="{{ route('expert.leads.index') }}" class="back-link">← Zurück</a>
    <h1 style="font-family:var(--font-display);margin-top:0;">{{ $lead->owner_name }}</h1>

    <dl class="detail-grid">
        <div><dt>E-Mail</dt><dd><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></dd></div>
        <div><dt>Telefon</dt><dd>{{ $lead->phone ?? '—' }}</dd></div>
        <div><dt>Kanton</dt><dd>{{ $lead->canton->name_de ?? '—' }}</dd></div>
        <div><dt>Status</dt><dd>{{ $lead->status }}</dd></div>
        <div><dt>Firma</dt><dd>{{ $lead->company_name ?? '—' }}</dd></div>
        <div><dt>Branche</dt><dd>{{ $lead->industry ?? '—' }}</dd></div>
    </dl>

    @if ($lead->message)
        <div class="card"><strong>Nachricht</strong><p style="margin:0.5rem 0 0;">{{ $lead->message }}</p></div>
    @endif

    <form method="post" action="{{ route('expert.leads.update-status', $lead) }}" class="card">
        @csrf
        @method('PATCH')
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" name="status" id="status">
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($lead->status === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn--small">Aktualisieren</button>
    </form>
@endsection
