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
            <p>Pro Liste mehrere Experten auf einmal setzen (Top 5, Top 3 je Kanton, Fachgruppen, …).</p>
        </div>
    </header>

    <div class="status-help">
        <strong>So funktioniert’s:</strong>
        <span>
            Wählen Sie unten eine Ranking-Art → öffnen Sie den <b>Gruppen-Editor</b> →
            belegen Sie Position 01, 02, 03… mit bestehenden Experten → einmal speichern.
        </span>
    </div>

    <div class="rk-launch">
        <h2 class="rk-launch__title">Neue / bestehende Liste bearbeiten</h2>
        <div class="rk-launch__grid">
            @foreach ($scopes as $key => $label)
                <form method="get" action="{{ route('admin.rankings.edit-group') }}" class="rk-launch__card">
                    <input type="hidden" name="scope" value="{{ $key }}">
                    <strong>{{ $label }}</strong>
                    <span>
                        @if ($key === 'national_top5') Bis 5 Experten national
                        @elseif ($key === 'canton_top3') Bis 3 Experten je Kanton
                        @elseif ($key === 'group_top') Bis 5 Experten je Fachgruppe
                        @elseif ($key === 'hero_match') 1 Top Match auf der Startseite
                        @elseif ($key === 'article_top') Bis 3 Fachbeiträge
                        @elseif ($key === 'event_top') Top Events
                        @else Mehrere Positionen
                        @endif
                    </span>

                    @if ($key === 'canton_top3')
                        <label class="rk-launch__field">
                            <em>Kanton</em>
                            <select name="canton_id" class="form-control" required>
                                @foreach ($cantons as $canton)
                                    <option value="{{ $canton->id }}">{{ $canton->code }} — {{ $canton->name_de }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    @if ($key === 'group_top')
                        <label class="rk-launch__field">
                            <em>Fachgruppe</em>
                            <select name="professional_group" class="form-control" required>
                                @foreach ($professionalGroups as $gKey => $gLabel)
                                    <option value="{{ $gKey }}">{{ $gLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <button type="submit" class="btn btn--small">Liste öffnen →</button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="admin-status-chips" role="group" style="margin-top:1.5rem;">
        <a class="admin-status-chip {{ $scope === '' ? 'is-on' : '' }}" href="{{ route('admin.rankings.index') }}">Alle Listen</a>
        @foreach ($scopes as $key => $label)
            <a class="admin-status-chip {{ $scope === $key ? 'is-on' : '' }}" href="{{ route('admin.rankings.index', ['scope' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="admin-card" style="margin-top:1rem;">
        @if ($groups->isEmpty())
            <div class="empty-state">Noch keine Rankings gespeichert. Öffnen Sie oben eine Liste und setzen Sie die Positionen.</div>
        @else
            <div class="rk-groups">
                @foreach ($groups as $group)
                    <article class="rk-group">
                        <header class="rk-group__head">
                            <div>
                                <p class="rk-group__kicker">{{ $group['scope_label'] }}</p>
                                <h3>
                                    @if ($group['canton'])
                                        {{ $group['canton']->code }} · {{ $group['canton']->name_de }}
                                    @elseif ($group['professional_group'])
                                        {{ $professionalGroups[$group['professional_group']] ?? $group['professional_group'] }}
                                    @else
                                        Gesamtliste
                                    @endif
                                </h3>
                                <p class="rk-group__meta">
                                    @if ($group['label']){{ $group['label'] }} · @endif
                                    {{ $group['starts_on']?->format('d.m.Y') ?? '—' }}
                                    →
                                    {{ $group['ends_on']?->format('d.m.Y') ?? '—' }}
                                    · {{ $group['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                </p>
                            </div>
                            <a class="btn btn--small" href="{{ $group['edit_url'] }}">Liste bearbeiten</a>
                        </header>
                        <ol class="rk-group__list">
                            @foreach ($group['items'] as $row)
                                <li>
                                    <b>{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</b>
                                    <span>
                                        @if ($row->expert)
                                            {{ $row->expert->company_name }}
                                        @elseif ($row->article)
                                            {{ $row->article->title }}
                                        @elseif ($row->event)
                                            {{ $row->event->title }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
