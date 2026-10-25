@extends('layouts.admin')

@section('title', $ranking ? 'Ranking bearbeiten' : 'Ranking anlegen')

@section('content')
<a href="{{ route('admin.rankings.index') }}" class="back-link">← Zurück</a>
<h1 style="font-family:var(--font-display);margin-top:0;">{{ $ranking ? 'Ranking bearbeiten' : 'Ranking anlegen' }}</h1>

@if ($errors->any())
    <div class="alert alert--danger" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.1rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="post" action="{{ $ranking ? route('admin.rankings.update', $ranking) : route('admin.rankings.store') }}" class="card" id="rankingForm">
    @csrf
    @if ($ranking) @method('PUT') @endif

    <div class="form-group">
        <label for="scope">Art der Platzierung *</label>
        <select class="form-control" name="scope" id="scope" required>
            @foreach ($scopes as $key => $label)
                <option value="{{ $key }}" @selected(old('scope', $ranking->scope ?? $scope) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="field-hint">Art wechseln lädt die passenden Felder neu.</p>
    </div>
    <script>
    document.getElementById('scope').addEventListener('change', function () {
        var scope = this.value;
        @if ($ranking)
            window.location = @json(route('admin.rankings.edit', $ranking)).split('?')[0] + '?scope=' + encodeURIComponent(scope);
        @else
            window.location = @json(route('admin.rankings.create')) + '?scope=' + encodeURIComponent(scope);
        @endif
    });
    </script>

    @php $currentScope = old('scope', $ranking->scope ?? $scope); @endphp

    <div class="form-row">
        <div class="form-group">
            <label for="position">Rang / Position *</label>
            <input class="form-control" type="number" name="position" id="position" min="1" max="{{ $maxPosition }}" value="{{ old('position', $ranking->position ?? 1) }}" required>
            <p class="field-hint">1 = erster Platz (max. {{ $maxPosition }} für diese Art).</p>
        </div>
        <div class="form-group">
            <label for="label">Bezeichnung <em class="label-optional">(optional)</em></label>
            <input class="form-control" type="text" name="label" id="label" value="{{ old('label', $ranking->label ?? '') }}" placeholder="z. B. Q1 2026">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="starts_on">Gültig von</label>
            <input class="form-control" type="date" name="starts_on" id="starts_on" value="{{ old('starts_on', optional($ranking->starts_on ?? null)?->format('Y-m-d')) }}">
        </div>
        <div class="form-group">
            <label for="ends_on">Gültig bis</label>
            <input class="form-control" type="date" name="ends_on" id="ends_on" value="{{ old('ends_on', optional($ranking->ends_on ?? null)?->format('Y-m-d')) }}">
        </div>
    </div>

    @if (in_array($currentScope, ['group_top', 'canton_top3', 'national_top5'], true))
        <div class="form-group">
            <label for="professional_group">Fachgruppe @if($currentScope === 'group_top')*@endif</label>
            <select class="form-control" name="professional_group" id="professional_group" @if($currentScope === 'group_top') required @endif>
                <option value="">— alle / keine —</option>
                @foreach ($groups as $key => $label)
                    <option value="{{ $key }}" @selected(old('professional_group', $ranking->professional_group ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="field-hint">Marktplätze erscheinen in eigener Liste — nicht auf der Karte.</p>
        </div>
    @else
        <input type="hidden" name="professional_group" value="">
    @endif

    @if ($currentScope === 'canton_top3')
        <div class="form-group">
            <label for="canton_id">Kanton *</label>
            <select class="form-control" name="canton_id" id="canton_id" required>
                <option value="">— wählen —</option>
                @foreach ($cantons as $canton)
                    <option value="{{ $canton->id }}" @selected((string) old('canton_id', $ranking->canton_id ?? '') === (string) $canton->id)>
                        {{ $canton->code }} — {{ $canton->name_de }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array($currentScope, ['hero_match', 'national_top5', 'canton_top3', 'group_top'], true))
        <div class="form-group">
            <label for="expert_id">Experte *</label>
            <select class="form-control" name="expert_id" id="expert_id" required>
                <option value="">— wählen —</option>
                @foreach ($experts as $expert)
                    <option value="{{ $expert->id }}" @selected((string) old('expert_id', $ranking->expert_id ?? '') === (string) $expert->id)>
                        {{ $expert->company_name }} ({{ $groups[$expert->professional_group] ?? $expert->professional_group }})
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($currentScope === 'article_top')
        <div class="form-group">
            <label for="article_id">Fachbeitrag *</label>
            <select class="form-control" name="article_id" id="article_id" required>
                <option value="">— wählen —</option>
                @foreach ($articles as $article)
                    <option value="{{ $article->id }}" @selected((string) old('article_id', $ranking->article_id ?? '') === (string) $article->id)>{{ $article->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($currentScope === 'event_top')
        <div class="form-group">
            <label for="event_id">Event *</label>
            <select class="form-control" name="event_id" id="event_id" required>
                <option value="">— wählen —</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $ranking->event_id ?? '') === (string) $event->id)>
                        {{ $event->title }}@if($event->starts_on) ({{ $event->starts_on->format('d.m.Y') }})@endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ranking->is_active ?? true))> Aktiv</label>
    </div>

    <button type="submit" class="btn">Speichern</button>
</form>
@endsection
