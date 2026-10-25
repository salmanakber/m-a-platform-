@extends('layouts.admin')

@section('title', 'Rankings / Top-Platzierungen')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Rankings</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Verkaufbare Platzierungen</p>
            <h1>Rankings &amp; Top-Listen</h1>
            <p>Manuell setzen: Position (1./2./3.…), Zeitraum von–bis, Fachgruppe oder Kanton. Kein Algorithmus.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--small" href="{{ route('admin.rankings.create') }}">Platzierung hinzufügen</a>
        </div>
    </header>

    <div class="status-help">
        <strong>Was gehört wohin?</strong>
        <span>
            <b>Hero Top Match</b> = Karte rechts auf der Startseite ·
            <b>Top 5 Schweiz</b> = nationale Rangliste ·
            <b>Top 3 Kanton</b> = je Kanton ·
            <b>Fachgruppe</b> = Top M&A / Juristen / Marktplätze (Marktplätze nicht auf der Karte) ·
            <b>Fachbeiträge / Events</b> = Top-Inhalte auf der Startseite.
        </span>
    </div>

    <div class="admin-status-chips" role="group">
        <a class="admin-status-chip {{ $scope === '' ? 'is-on' : '' }}" href="{{ route('admin.rankings.index') }}">Alle</a>
        @foreach ($scopes as $key => $label)
            <a class="admin-status-chip {{ $scope === $key ? 'is-on' : '' }}" href="{{ route('admin.rankings.index', ['scope' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="admin-card">
        @if ($rankings->isEmpty())
            <div class="empty-state">Noch keine Rankings. Legen Sie die erste Platzierung an.</div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>Scope</th>
                            <th>Pos.</th>
                            <th>Ziel</th>
                            <th>Gruppe / Kanton</th>
                            <th>Zeitraum</th>
                            <th>Aktiv</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rankings as $row)
                            <tr>
                                <td>{{ $scopes[$row->scope] ?? $row->scope }}</td>
                                <td>{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    @if ($row->expert)
                                        {{ $row->expert->company_name }}
                                    @elseif ($row->article)
                                        Artikel: {{ $row->article->title }}
                                    @elseif ($row->event)
                                        Event: {{ $row->event->title }}
                                    @else
                                        —
                                    @endif
                                    @if ($row->label)<div class="entity-sub">{{ $row->label }}</div>@endif
                                </td>
                                <td>
                                    {{ $row->professional_group ? ($groups[$row->professional_group] ?? $row->professional_group) : '—' }}
                                    @if ($row->canton)<div class="entity-sub">{{ $row->canton->code }} {{ $row->canton->name_de }}</div>@endif
                                </td>
                                <td>
                                    {{ $row->starts_on?->format('d.m.Y') ?? '—' }}
                                    →
                                    {{ $row->ends_on?->format('d.m.Y') ?? '—' }}
                                </td>
                                <td>{{ $row->is_active ? 'Ja' : 'Nein' }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn--ghost btn--small" href="{{ route('admin.rankings.edit', $row) }}">Bearbeiten</a>
                                        <form method="post" action="{{ route('admin.rankings.destroy', $row) }}" onsubmit="return confirm('Löschen?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-card__foot">{{ $rankings->links() }}</div>
        @endif
    </div>
</div>
@endsection
