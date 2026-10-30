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
            'status' => $statusLabel,
            'search' => mb_strtolower(trim(
                $expert->company_name.' '.$person.' '.$place.' '.$groupLabel.' '.$statusLabel.' '.($expert->email ?? '')
            )),
        ];
    })->values();

    $contextTitle = $scopes[$scope] ?? $scope;
    if ($scope === 'canton_top3') {
        $c = $cantons->firstWhere('id', $cantonId);
        $contextTitle .= $c ? ' · '.$c->code.' '.$c->name_de : '';
    }
    if ($scope === 'group_top') {
        $contextTitle .= ' · '.($professionalGroups[$professionalGroup] ?? $professionalGroup);
    }
@endphp

<div class="admin-page rk-simple">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Schritt bearbeiten</p>
            <h1>{{ $contextTitle }}</h1>
            <p>{{ \App\Support\RankingScope::help($scope) }} Setzen Sie Position 1–{{ $maxPosition }} und speichern Sie einmal.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.rankings.index') }}">← Alle Listen</a>
        </div>
    </header>

    @if ($errors->any())
        <div class="alert alert--danger" style="margin-bottom:1rem;">
            <ul style="margin:0;padding-left:1.1rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($scope === 'canton_top3' || $scope === 'group_top' || $scope === 'article_top')
        <form method="get" action="{{ route('admin.rankings.edit-group') }}" class="rk-context card">
            <input type="hidden" name="scope" value="{{ $scope }}">
            @if ($scope === 'canton_top3')
                <label>
                    <span>Anderen Kanton wählen</span>
                    <select class="form-control" name="canton_id" onchange="this.form.submit()">
                        @foreach ($cantons as $canton)
                            <option value="{{ $canton->id }}" @selected((int) $cantonId === (int) $canton->id)>
                                {{ $canton->code }} — {{ $canton->name_de }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($scope === 'group_top')
                <label>
                    <span>Andere Fachgruppe wählen</span>
                    <select class="form-control" name="professional_group" onchange="this.form.submit()">
                        @foreach ($professionalGroups as $key => $label)
                            <option value="{{ $key }}" @selected($professionalGroup === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($scope === 'article_top')
                <label>
                    <span>Fachbereich-Gruppe (Top 3)</span>
                    <select class="form-control" name="article_category_id" onchange="this.form.submit()">
                        <option value="" @selected(empty($articleCategoryId))>Global (Startseite)</option>
                        @foreach (($articleCategories ?? []) as $cat)
                            <option value="{{ $cat->id }}" @selected((int) ($articleCategoryId ?? 0) === (int) $cat->id)>{{ $cat->name_de }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </form>
    @endif

    <form method="post" action="{{ route('admin.rankings.save-group') }}" class="card rk-editor" id="rankingGroupForm">
        @csrf
        <input type="hidden" name="scope" value="{{ $scope }}">
        @if ($scope === 'canton_top3')
            <input type="hidden" name="canton_id" value="{{ $cantonId }}">
        @endif
        @if ($scope === 'group_top')
            <input type="hidden" name="professional_group" value="{{ $professionalGroup }}">
        @endif
        @if ($scope === 'article_top' && ! empty($articleCategoryId))
            <input type="hidden" name="article_category_id" value="{{ $articleCategoryId }}">
        @endif

        <p class="rk-editor__hint">Jedes Ranking-Slot hat eigene Gültigkeit (Von / Bis). Leere Slots werden nicht gespeichert.</p>

        <div class="rk-slots">
            @for ($position = 1; $position <= $maxPosition; $position++)
                @php
                    $slot = $slots->get($position);
                    $oldSlot = old('slots.'.$position);
                    $slotStarts = $oldSlot['starts_on'] ?? optional($slot?->starts_on)->format('Y-m-d');
                    $slotEnds = $oldSlot['ends_on'] ?? optional($slot?->ends_on)->format('Y-m-d');
                    $slotLabel = $oldSlot['label'] ?? ($slot?->label ?? '');
                    $slotActive = array_key_exists('is_active', (array) $oldSlot)
                        ? (bool) ($oldSlot['is_active'] ?? false)
                        : (bool) ($slot?->is_active ?? true);
                @endphp
                <div class="rk-slot">
                    <div class="rk-slot__rank">
                        <span>Rang</span>
                        <b>{{ $position }}</b>
                    </div>
                    <div class="rk-slot__body">
                        <input type="hidden" name="slots[{{ $position }}][position]" value="{{ $position }}">

                        @if ($slotKind === 'expert')
                            @php $selectedId = (string) ($oldSlot['expert_id'] ?? $slot?->expert_id ?? ''); @endphp
                            <div class="rk-expert-picker" data-expert-picker>
                                <input type="hidden" name="slots[{{ $position }}][expert_id]" value="{{ $selectedId }}" data-expert-id>
                                <div class="rk-expert-picker__wrap">
                                    <input class="form-control" type="search" placeholder="Experte suchen (Firma oder Name)…" autocomplete="off" data-expert-search>
                                    <div class="rk-expert-picker__list" data-expert-list hidden role="listbox"></div>
                                </div>
                                <div class="rk-slot__foot">
                                    <p class="rk-expert-picker__selected" data-expert-selected @if($selectedId === '') hidden @endif></p>
                                    <button type="button" class="btn btn--ghost btn--small" data-expert-clear @if($selectedId === '') hidden @endif>Leeren</button>
                                </div>
                            </div>
                        @elseif ($slotKind === 'article')
                            @php $selectedId = (string) ($oldSlot['article_id'] ?? $slot?->article_id ?? ''); @endphp
                            <select class="form-control" name="slots[{{ $position }}][article_id]">
                                <option value="">— nicht gesetzt —</option>
                                @foreach ($articles as $article)
                                    <option value="{{ $article->id }}" @selected($selectedId === (string) $article->id)>{{ $article->title }}</option>
                                @endforeach
                            </select>
                        @else
                            @php $selectedId = (string) ($oldSlot['event_id'] ?? $slot?->event_id ?? ''); @endphp
                            <select class="form-control" name="slots[{{ $position }}][event_id]">
                                <option value="">— nicht gesetzt —</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" @selected($selectedId === (string) $event->id)>
                                        {{ $event->title }}@if($event->starts_on) ({{ $event->starts_on->format('d.m.Y') }})@endif
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <div class="rk-slot__dates form-row">
                            <div class="form-group">
                                <label>Bezeichnung <em class="label-optional">(optional)</em></label>
                                <input class="form-control" type="text" name="slots[{{ $position }}][label]" value="{{ $slotLabel }}" placeholder="z. B. Q1 2026">
                            </div>
                            <div class="form-group">
                                <label>Von</label>
                                <input class="form-control" type="date" name="slots[{{ $position }}][starts_on]" value="{{ $slotStarts }}">
                            </div>
                            <div class="form-group">
                                <label>Bis</label>
                                <input class="form-control" type="date" name="slots[{{ $position }}][ends_on]" value="{{ $slotEnds }}">
                            </div>
                            <div class="form-group" style="align-self:end;">
                                <label class="rk-active">
                                    <input type="checkbox" name="slots[{{ $position }}][is_active]" value="1" @checked($slotActive)> Aktiv
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <div class="rk-editor__actions">
            <button type="submit" class="btn">Liste speichern</button>
            <a class="btn btn--ghost" href="{{ route('admin.rankings.index') }}">Abbrechen</a>
        </div>
    </form>
</div>

@if ($slotKind === 'expert')
<script type="application/json" id="rankingExpertsJson">@json($expertJson)</script>
<script>
(function () {
    var experts = [];
    try { experts = JSON.parse(document.getElementById('rankingExpertsJson').textContent || '[]'); } catch (e) {}

    function findById(id) {
        id = String(id || '');
        for (var i = 0; i < experts.length; i++) if (experts[i].id === id) return experts[i];
        return null;
    }

    document.querySelectorAll('[data-expert-picker]').forEach(function (root) {
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
            selected.innerHTML = '<strong>' + item.company + '</strong>'
                + (item.person ? '<span> — ' + item.person + '</span>' : '')
                + (item.place ? '<span> · ' + item.place + '</span>' : '');
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
                return !q || item.search.indexOf(q) !== -1;
            }).slice(0, 50);
        }

        function render() {
            var rows = filtered();
            list.innerHTML = '';
            if (!rows.length) {
                list.innerHTML = '<div class="rk-expert-picker__empty">Kein Treffer</div>';
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
                btn.addEventListener('mousedown', function (e) { e.preventDefault(); setSelected(item); });
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
        document.addEventListener('click', function (e) { if (!root.contains(e.target)) list.hidden = true; });
        if (clearBtn) clearBtn.addEventListener('click', function () { setSelected(null); });
        setSelected(findById(hidden.value));
    });
})();
</script>
@endif
@endsection
