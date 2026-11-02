@extends('layouts.expert')

@section('title', 'CV Generator')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Lebenslauf</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-promo-hero">
        <div>
            <p class="xp-hero__kicker">Dokumente</p>
            <h1>CV Generator</h1>
            <p>Lebenslauf aus Ihrem Profil erstellen, manuell ergänzen und optional mit KI ausformulieren. Templates wählen oder von der KI vorschlagen lassen.</p>
        </div>
        <form method="post" action="{{ route('expert.cv.store') }}">
            @csrf
            <div class="form-group" style="margin:0 0 0.5rem;">
                <label for="template_key">Template</label>
                <select class="form-control" name="template_key" id="template_key">
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <label style="display:flex;gap:0.4rem;align-items:center;margin:0 0 0.65rem;font-size:0.9rem;">
                <input type="checkbox" name="use_ai_template" value="1"> KI-Template-Vorschlag
            </label>
            <button type="submit" class="btn">Neuen CV erstellen</button>
        </form>
    </header>

    @if ($cvs->isEmpty())
        <div class="xp-empty">
            <strong>Noch kein Lebenslauf</strong>
            <p>Starten Sie mit «Neuen CV erstellen» — Felder werden aus dem Profil übernommen.</p>
        </div>
    @else
        <div class="table-scroll">
            <table class="entity-table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Aktualisiert</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cvs as $cv)
                        <tr>
                            <td><strong>{{ $cv->title }}</strong></td>
                            <td>{{ $templates[$cv->template_key] ?? $cv->template_key }}</td>
                            <td>{{ $cv->status === 'ready' ? 'Final' : 'Entwurf' }}</td>
                            <td>{{ $cv->updated_at?->format('d.m.Y H:i') }}</td>
                            <td style="text-align:right;">
                                <a class="btn btn--small btn--ghost" href="{{ route('expert.cv.edit', $cv) }}">Bearbeiten</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
