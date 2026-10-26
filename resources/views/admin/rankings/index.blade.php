@extends('layouts.admin')

@section('title', 'Rankings / Top-Platzierungen')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Rankings</span>
@endsection

@section('content')
<div class="admin-page rk-simple">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Startseite steuern</p>
            <h1>Top-Listen setzen</h1>
            <p>Experten aus der bestehenden Liste wählen und Rang 1, 2, 3… zuweisen — fertig.</p>
        </div>
    </header>

    <ol class="rk-steps">
        <li><b>1</b><span>Liste wählen</span></li>
        <li><b>2</b><span>Experten suchen &amp; auf Positionen setzen</span></li>
        <li><b>3</b><span>Speichern</span></li>
    </ol>

    <section class="rk-actions">
        <h2>Welche Liste möchten Sie bearbeiten?</h2>
        <div class="rk-actions__grid">
            @foreach ($scopes as $key => $label)
                <a class="rk-action"
                   href="{{ route('admin.rankings.edit-group', array_filter([
                       'scope' => $key,
                       'canton_id' => $key === 'canton_top3' ? optional($cantons->firstWhere('code', 'ZH') ?? $cantons->first())->id : null,
                       'professional_group' => $key === 'group_top' ? 'ma_specialist' : null,
                   ])) }}">
                    <strong>{{ $label }}</strong>
                    <span>{{ \App\Support\RankingScope::help($key) }}</span>
                    <em>Bearbeiten →</em>
                </a>
            @endforeach
        </div>
    </section>

    @if ($scopes['canton_top3'] ?? false)
        <section class="rk-quick card">
            <h3>Top 3 für einen anderen Kanton</h3>
            <form method="get" action="{{ route('admin.rankings.edit-group') }}" class="rk-quick__form">
                <input type="hidden" name="scope" value="canton_top3">
                <select class="form-control" name="canton_id" required>
                    @foreach ($cantons as $canton)
                        <option value="{{ $canton->id }}">{{ $canton->code }} — {{ $canton->name_de }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn--small">Öffnen</button>
            </form>
        </section>
    @endif

    @if ($scopes['group_top'] ?? false)
        <section class="rk-quick card">
            <h3>Top-Liste für eine Fachgruppe</h3>
            <form method="get" action="{{ route('admin.rankings.edit-group') }}" class="rk-quick__form">
                <input type="hidden" name="scope" value="group_top">
                <select class="form-control" name="professional_group" required>
                    @foreach ($professionalGroups as $gKey => $gLabel)
                        <option value="{{ $gKey }}">{{ $gLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn--small">Öffnen</button>
            </form>
        </section>
    @endif

    <section class="admin-card" style="margin-top:1.5rem;">
        <div class="admin-card__head admin-card__head--row">
            <h2>Aktuell gespeichert</h2>
        </div>
        @if ($groups->isEmpty())
            <div class="empty-state">Noch nichts gesetzt. Wählen Sie oben eine Liste.</div>
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
                                        Aktuelle Liste
                                    @endif
                                </h3>
                                <p class="rk-group__meta">
                                    {{ $group['items']->count() }} Position{{ $group['items']->count() === 1 ? '' : 'en' }}
                                    @if ($group['label']) · {{ $group['label'] }} @endif
                                    · {{ $group['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                </p>
                            </div>
                            <a class="btn btn--small" href="{{ $group['edit_url'] }}">Ändern</a>
                        </header>
                        <ol class="rk-group__list">
                            @foreach ($group['items'] as $row)
                                <li>
                                    <b>{{ str_pad((string) $row->position, 2, '0', STR_PAD_LEFT) }}</b>
                                    <span>
                                        @if ($row->expert) {{ $row->expert->company_name }}
                                        @elseif ($row->article) {{ $row->article->title }}
                                        @elseif ($row->event) {{ $row->event->title }}
                                        @else — @endif
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
