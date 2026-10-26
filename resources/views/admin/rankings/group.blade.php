@extends('layouts.admin')

@section('title', ($scopes[$scope] ?? 'Ranking').' bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.rankings.index') }}">Rankings</a>
    <span>/</span>
    <span>{{ $scopes[$scope] ?? $scope }}</span>
@endsection

@section('content')
@php
    $expertJson = $experts->map(function ($expert) use ($professionalGroups, $statusLabels) {
        $office = $expert->offices->firstWhere('is_primary', true) ?? $expert->offices->first();
        $person = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));
        $place = $office
            ? trim(($office->city ?? '').(($office->canton?->code) ? ' · '.$office->canton->code : ''))
            : '';
        $groupLabel = $professionalGroups[$expert->professional_group] ?? ($expert->professional_group ?: '—');
        $statusLabel = $statusLabels[$expert->status] ?? $expert->status;

        return [
            'id' => (string) $expert->id,
            'company' => $expert->company_name,
            'person' => $person,
            'place' => $place,
            'group' => $groupLabel,
            'group_key' => (string) ($expert->professional_group ?? ''),
            'status' => $statusLabel,
            'search' => mb_strtolower(trim(
                $expert->company_name.' '.$person.' '.$place.' '.$groupLabel.' '.$statusLabel.' '.($expert->email ?? '')
            )),
        ];
    })->values();
@endphp

<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Gruppen-Editor</p>
            <h1>{{ $scopes[$scope] ?? $scope }}</h1>
            <p>
                Alle Positionen (1–{{ $maxPosition }}) in einem Schritt setzen.
                Leere Slots werden nicht gespeichert.
            </p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.rankings.index') }}">← Übersicht</a>
        </div>
    </header>

    @if ($errors->any())
        <div class="alert alert--danger" style="margin-bottom:1rem;">
            <ul style="margin:0;padding-left:1.1rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="get" action="{{ route('admin.rankings.edit-group') }}" class="card" style="margin-bottom:1rem;">
        <div class="form-row">
            <div class="form-group">
                <label for="switch_scope">Ranking-Art</label>
                <select class="form-control" name="scope" id="switch_scope" onchange="this.form.submit()">
                    @foreach ($scopes as $key => $label)
                        <option value="{{ $key }}" @selected($scope === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($scope === 'canton_top3')
                <div class="form-group">
                    <label for="switch_canton">Kanton</label>
                    <select class="form-control" name="canton_id" id="switch_canton" onchange="this.form.submit()">
                        @foreach ($cantons as $canton)
                            <option value="{{ $canton->id }}" @selected((int) $cantonId === (int) $canton->id)>
                                {{ $canton->code }} — {{ $canton->name_de }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($scope === 'group_top')
                <div class="form-group">
                    <label for="switch_group">Fachgruppe</label>
                    <select class="form-control" name="professional_group" id="switch_group" onchange="this.form.submit()">
                        @foreach ($professionalGroups as $key => $label)
                            <option value="{{ $key }}" @selected($professionalGroup === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <p class="field-hint" style="margin:0;">Art / Kanton / Fachgruppe wechseln lädt die passende Liste.</p>
    </form>

    <form method="post" action="{{ route('admin.rankings.save-group') }}" class="card" id="rankingGroupForm">
        @csrf
        <input type="hidden" name="scope" value="{{ $scope }}">
        @if ($scope === 'canton_top3')
            <input type="hidden" name="canton_id" value="{{ $cantonId }}">
        @endif
        @if ($scope === 'group_top')
            <input type="hidden" name="professional_group" value="{{ $professionalGroup }}">
        @endif

        <div class="form-row">
            <div class="form-group">
                <label for="label">Zeitraum-Bezeichnung <em class="label-optional">(optional)</em></label>
                <input class="form-control" type="text" name="label" id="label" value="{{ old('label', $meta['label'] ?? '') }}" placeholder="z. B. Q1 2026">
            </div>
            <div class="form-group">
                <label for="starts_on">Gültig von</label>
                <input class="form-control" type="date" name="starts_on" id="starts_on" value="{{ old('starts_on', $meta['starts_on'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="ends_on">Gültig bis</label>
                <input class="form-control" type="date" name="ends_on" id="ends_on" value="{{ old('ends_on', $meta['ends_on'] ?? '') }}">
            </div>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $meta['is_active'] ?? true))> Liste aktiv</label>
        </div>

        <h2 style="font-family:var(--font-display);font-size:1.25rem;margin:1.25rem 0 0.75rem;">
            Positionen (bis {{ $maxPosition }})
        </h2>
        <p class="field-hint" style="margin-top:0;">Wählen Sie für jede Position einen bestehenden Eintrag. Speichern ersetzt die ganze Liste.</p>

        <div class="rk-slots">
            @for ($position = 1; $position <= $maxPosition; $position++)
                @php
                    /** @var \App\Models\Ranking|null $slot */
                    $slot = $slots->get($position);
                    $oldSlot = old('slots.'.$position);
                @endphp
                <div class="rk-slot" data-slot="{{ $position }}">
                    <div class="rk-slot__rank">{{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="rk-slot__body">
                        <input type="hidden" name="slots[{{ $position }}][position]" value="{{ $position }}">

                        @if ($slotKind === 'expert')
                            @php
                                $selectedId = (string) ($oldSlot['expert_id'] ?? $slot?->expert_id ?? '');
                            @endphp
                            <label class="rk-slot__label">Experte</label>
                            <div class="rk-expert-picker" data-expert-picker data-slot-picker="{{ $position }}">
                                <input type="hidden" name="slots[{{ $position }}][expert_id]" value="{{ $selectedId }}" data-expert-id>
                                <div class="rk-expert-picker__wrap">
                                    <input
                                        class="form-control"
                                        type="search"
                                        placeholder="Firma / Person suchen…"
                                        autocomplete="off"
                                        data-expert-search
                                    >
                                    <div class="rk-expert-picker__list" data-expert-list hidden role="listbox"></div>
                                </div>
                                <p class="rk-expert-picker__selected" data-expert-selected @if($selectedId === '') hidden @endif></p>
                                <button type="button" class="btn btn--ghost btn--small" data-expert-clear @if($selectedId === '') hidden @endif>Slot leeren</button>
                            </div>
                        @elseif ($slotKind === 'article')
                            @php $selectedId = (string) ($oldSlot['article_id'] ?? $slot?->article_id ?? ''); @endphp
                            <label class="rk-slot__label" for="article_{{ $position }}">Fachbeitrag</label>
                            <select class="form-control" name="slots[{{ $position }}][article_id]" id="article_{{ $position }}">
                                <option value="">— leer —</option>
                                @foreach ($articles as $article)
                                    <option value="{{ $article->id }}" @selected($selectedId === (string) $article->id)>{{ $article->title }}</option>
                                @endforeach
                            </select>
                        @else
                            @php $selectedId = (string) ($oldSlot['event_id'] ?? $slot?->event_id ?? ''); @endphp
                            <label class="rk-slot__label" for="event_{{ $position }}">Event</label>
                            <select class="form-control" name="slots[{{ $position }}][event_id]" id="event_{{ $position }}">
                                <option value="">— leer —</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" @selected($selectedId === (string) $event->id)>
                                        {{ $event->title }}@if($event->starts_on) ({{ $event->starts_on->format('d.m.Y') }})@endif
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            @endfor
        </div>

        <div style="margin-top:1.25rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
            <button type="submit" class="btn">Gesamte Liste speichern</button>
            <a class="btn btn--ghost" href="{{ route('admin.rankings.index') }}">Abbrechen</a>
        </div>
    </form>
</div>

@if ($slotKind === 'expert')
<script type="application/json" id="rankingExpertsJson">@json($expertJson)</script>
<script>
(function () {
    var jsonEl = document.getElementById('rankingExpertsJson');
    var experts = [];
    try { experts = JSON.parse(jsonEl.textContent || '[]'); } catch (e) { experts = []; }

    function findById(id) {
        id = String(id || '');
        for (var i = 0; i < experts.length; i++) if (experts[i].id === id) return experts[i];
        return null;
    }

    function initPicker(root) {
        var hidden = root.querySelector('[data-expert-id]');
        var search = root.querySelector('[data-expert-search]');
        var list = root.querySelector('[data-expert-list]');
        var selected = root.querySelector('[data-expert-selected]');
        var clearBtn = root.querySelector('[data-expert-clear]');

        function setSelected(item) {
            if (!item) {
                hidden.value = '';
                selected.hidden = true;
                selected.textContent = '';
                search.value = '';
                if (clearBtn) clearBtn.hidden = true;
                return;
            }
            hidden.value = item.id;
            selected.hidden = false;
            selected.innerHTML = '<strong>Gewählt:</strong> ' + item.company
                + (item.person ? ' — ' + item.person : '')
                + (item.place ? ' · ' + item.place : '')
                + ' · ' + item.group;
            search.value = item.company + (item.person ? ' — ' + item.person : '');
            list.hidden = true;
            if (clearBtn) clearBtn.hidden = false;
        }

        function filtered() {
            var q = (search.value || '').trim().toLowerCase();
            var taken = {};
            document.querySelectorAll('[data-expert-id]').forEach(function (el) {
                if (el !== hidden && el.value) taken[el.value] = true;
            });
            return experts.filter(function (item) {
                if (taken[item.id] && item.id !== hidden.value) return false;
                if (!q) return true;
                return item.search.indexOf(q) !== -1;
            }).slice(0, 60);
        }

        function render() {
            var rows = filtered();
            list.innerHTML = '';
            if (!rows.length) {
                list.innerHTML = '<div class="rk-expert-picker__empty">Kein Experte gefunden.</div>';
                list.hidden = false;
                return;
            }
            rows.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rk-expert-picker__item' + (hidden.value === item.id ? ' is-on' : '');
                btn.innerHTML = '<strong></strong><span></span>';
                btn.querySelector('strong').textContent = item.company + (item.person ? ' — ' + item.person : '');
                btn.querySelector('span').textContent = [item.place, item.group, item.status].filter(Boolean).join(' · ');
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    setSelected(item);
                });
                list.appendChild(btn);
            });
            list.hidden = false;
        }

        search.addEventListener('focus', render);
        search.addEventListener('input', render);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') list.hidden = true;
            if (e.key === 'Enter') {
                e.preventDefault();
                var first = list.querySelector('.rk-expert-picker__item');
                if (first && !list.hidden) first.dispatchEvent(new Event('mousedown'));
            }
        });
        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) list.hidden = true;
        });
        if (clearBtn) {
            clearBtn.addEventListener('click', function () { setSelected(null); });
        }

        setSelected(findById(hidden.value));
    }

    document.querySelectorAll('[data-expert-picker]').forEach(initPicker);
})();
</script>
@endif
@endsection
