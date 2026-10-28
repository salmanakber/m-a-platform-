<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Canton;
use App\Models\Event;
use App\Models\Expert;
use App\Models\Ranking;
use App\Support\ArticleStatus;
use App\Support\EventStatus;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
use App\Support\RankingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(Request $request): View
    {
        $scope = (string) $request->query('scope', '');

        $rankings = Ranking::query()
            ->with(['expert', 'article', 'event', 'canton'])
            ->when($scope !== '', fn ($q) => $q->where('scope', $scope))
            ->orderBy('scope')
            ->orderBy('professional_group')
            ->orderBy('canton_id')
            ->orderBy('position')
            ->get();

        $groups = $this->buildGroupSummaries($rankings);

        return view('admin.rankings.index', [
            'groups' => $groups,
            'scope' => $scope,
            'scopes' => RankingScope::labels(),
            'professionalGroups' => ProfessionalGroup::labels(),
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function editGroup(Request $request): View
    {
        $scope = (string) $request->query('scope', RankingScope::NATIONAL_TOP5);
        if (! in_array($scope, RankingScope::all(), true)) {
            $scope = RankingScope::NATIONAL_TOP5;
        }

        $cantonId = $request->query('canton_id');
        $groupKey = $request->query('professional_group');

        if ($scope === RankingScope::CANTON_TOP3 && ! $cantonId) {
            // Default ZH if present
            $cantonId = optional(Canton::query()->where('code', 'ZH')->first())->id
                ?? optional(Canton::query()->orderBy('name_de')->first())->id;
        }

        if ($scope === RankingScope::GROUP_TOP && ! $groupKey) {
            $groupKey = ProfessionalGroup::MA_SPECIALIST;
        }

        $slots = $this->loadSlots($scope, $cantonId ? (int) $cantonId : null, $groupKey ?: null);
        $meta = $this->groupMeta($slots);

        return view('admin.rankings.group', [
            'scope' => $scope,
            'scopes' => RankingScope::labels(),
            'maxPosition' => RankingScope::maxPosition($scope),
            'slotKind' => $this->slotKind($scope),
            'cantonId' => $cantonId ? (int) $cantonId : null,
            'professionalGroup' => $groupKey ?: null,
            'professionalGroups' => ProfessionalGroup::labels(),
            'cantons' => Canton::query()->orderBy('name_de')->get(),
            'slots' => $slots,
            'meta' => $meta,
            'experts' => $this->expertOptions(),
            'articles' => Article::query()
                ->where('status', ArticleStatus::PUBLISHED)
                ->orderByDesc('published_at')
                ->limit(300)
                ->get(['id', 'title']),
            'events' => Event::query()
                ->where('status', EventStatus::PUBLISHED)
                ->orderByDesc('starts_on')
                ->limit(300)
                ->get(['id', 'title', 'starts_on']),
            'statusLabels' => [
                ExpertStatus::APPROVED => 'Freigegeben',
                ExpertStatus::PENDING => 'Ausstehend',
                ExpertStatus::DISABLED => 'Deaktiviert',
                ExpertStatus::REJECTED => 'Abgelehnt',
            ],
        ]);
    }

    public function saveGroup(Request $request): RedirectResponse
    {
        $scope = (string) $request->input('scope');
        if (! in_array($scope, RankingScope::all(), true)) {
            throw ValidationException::withMessages(['scope' => 'Ungültige Ranking-Art.']);
        }

        $max = RankingScope::maxPosition($scope);
        $slotKind = $this->slotKind($scope);

        $rules = [
            'scope' => ['required', Rule::in(RankingScope::all())],
            'professional_group' => ['nullable', Rule::in(ProfessionalGroup::all())],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'slots' => ['required', 'array', 'max:'.$max],
            'slots.*.position' => ['required', 'integer', 'min:1', 'max:'.$max],
            'slots.*.label' => ['nullable', 'string', 'max:120'],
            'slots.*.starts_on' => ['nullable', 'date'],
            'slots.*.ends_on' => ['nullable', 'date', 'after_or_equal:slots.*.starts_on'],
            'slots.*.is_active' => ['nullable', 'boolean'],
        ];

        if ($slotKind === 'expert') {
            $rules['slots.*.expert_id'] = ['nullable', 'exists:experts,id'];
        } elseif ($slotKind === 'article') {
            $rules['slots.*.article_id'] = ['nullable', 'exists:articles,id'];
        } else {
            $rules['slots.*.event_id'] = ['nullable', 'exists:events,id'];
        }

        $data = $request->validate($rules);
        $cantonId = ! empty($data['canton_id']) ? (int) $data['canton_id'] : null;
        $groupKey = $data['professional_group'] ?? null;

        if ($scope === RankingScope::CANTON_TOP3 && ! $cantonId) {
            throw ValidationException::withMessages(['canton_id' => 'Bitte einen Kanton wählen.']);
        }
        if ($scope === RankingScope::GROUP_TOP && ! $groupKey) {
            throw ValidationException::withMessages(['professional_group' => 'Bitte eine Fachgruppe wählen.']);
        }

        $filled = collect($data['slots'] ?? [])
            ->map(function (array $slot) use ($slotKind) {
                $position = (int) ($slot['position'] ?? 0);
                $targetId = null;
                if ($slotKind === 'expert') {
                    $targetId = ! empty($slot['expert_id']) ? (int) $slot['expert_id'] : null;
                } elseif ($slotKind === 'article') {
                    $targetId = ! empty($slot['article_id']) ? (int) $slot['article_id'] : null;
                } else {
                    $targetId = ! empty($slot['event_id']) ? (int) $slot['event_id'] : null;
                }

                return [
                    'position' => $position,
                    'target_id' => $targetId,
                    'label' => isset($slot['label']) ? trim((string) $slot['label']) : null,
                    'starts_on' => $slot['starts_on'] ?? null,
                    'ends_on' => $slot['ends_on'] ?? null,
                    'is_active' => ! empty($slot['is_active']),
                ];
            })
            ->filter(fn (array $slot) => $slot['position'] >= 1 && $slot['target_id'])
            ->sortBy('position')
            ->values();

        // No duplicate targets in one list
        $ids = $filled->pluck('target_id');
        if ($ids->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages([
                'slots' => 'Dieselbe Auswahl darf in einer Liste nicht mehrfach vorkommen.',
            ]);
        }

        // No duplicate positions
        $positions = $filled->pluck('position');
        if ($positions->count() !== $positions->unique()->count()) {
            throw ValidationException::withMessages([
                'slots' => 'Jede Position darf nur einmal vergeben werden.',
            ]);
        }

        DB::transaction(function () use ($scope, $cantonId, $groupKey, $filled, $slotKind) {
            $this->groupQuery($scope, $cantonId, $groupKey)->delete();

            foreach ($filled as $slot) {
                Ranking::query()->create([
                    'scope' => $scope,
                    'professional_group' => $scope === RankingScope::GROUP_TOP ? $groupKey : ($groupKey ?: null),
                    'canton_id' => $scope === RankingScope::CANTON_TOP3 ? $cantonId : null,
                    'expert_id' => $slotKind === 'expert' ? $slot['target_id'] : null,
                    'article_id' => $slotKind === 'article' ? $slot['target_id'] : null,
                    'event_id' => $slotKind === 'event' ? $slot['target_id'] : null,
                    'position' => $slot['position'],
                    'starts_on' => $slot['starts_on'] ?: null,
                    'ends_on' => $slot['ends_on'] ?: null,
                    'is_active' => (bool) $slot['is_active'],
                    'label' => $slot['label'] !== '' ? $slot['label'] : null,
                ]);
            }
        });

        return redirect()
            ->route('admin.rankings.edit-group', array_filter([
                'scope' => $scope,
                'canton_id' => $cantonId,
                'professional_group' => $groupKey,
            ]))
            ->with('success', RankingScope::label($scope).' gespeichert ('.$filled->count().' Platzierungen).');
    }

    public function destroy(Ranking $ranking): RedirectResponse
    {
        $scope = $ranking->scope;
        $cantonId = $ranking->canton_id;
        $groupKey = $ranking->professional_group;
        $ranking->delete();

        return redirect()
            ->route('admin.rankings.edit-group', array_filter([
                'scope' => $scope,
                'canton_id' => $cantonId,
                'professional_group' => $groupKey,
            ]))
            ->with('success', 'Platzierung entfernt.');
    }

    /**
     * @param  Collection<int, Ranking>  $rankings
     * @return Collection<int, array<string, mixed>>
     */
    private function buildGroupSummaries(Collection $rankings): Collection
    {
        return $rankings
            ->groupBy(function (Ranking $row) {
                return $row->scope.'|'.($row->professional_group ?? '').'|'.($row->canton_id ?? '');
            })
            ->map(function (Collection $rows) {
                /** @var Ranking $first */
                $first = $rows->sortBy('position')->first();

                return [
                    'scope' => $first->scope,
                    'scope_label' => RankingScope::label($first->scope),
                    'professional_group' => $first->professional_group,
                    'canton_id' => $first->canton_id,
                    'canton' => $first->canton,
                    'label' => $first->label,
                    'starts_on' => $first->starts_on,
                    'ends_on' => $first->ends_on,
                    'is_active' => $rows->contains(fn (Ranking $r) => $r->is_active),
                    'items' => $rows->sortBy('position')->values(),
                    'edit_url' => route('admin.rankings.edit-group', array_filter([
                        'scope' => $first->scope,
                        'canton_id' => $first->canton_id,
                        'professional_group' => $first->professional_group,
                    ])),
                ];
            })
            ->sortBy(fn (array $g) => $g['scope_label'].($g['canton']->code ?? '').($g['professional_group'] ?? ''))
            ->values();
    }

    /**
     * @return Collection<int, Ranking|null>
     */
    private function loadSlots(string $scope, ?int $cantonId, ?string $groupKey): Collection
    {
        $max = RankingScope::maxPosition($scope);
        $existing = $this->groupQuery($scope, $cantonId, $groupKey)
            ->with(['expert.offices.canton', 'article', 'event'])
            ->get()
            ->keyBy('position');

        $slots = collect();
        for ($i = 1; $i <= $max; $i++) {
            $slots->put($i, $existing->get($i));
        }

        return $slots;
    }

    /**
     * @param  Collection<int, Ranking|null>  $slots
     * @return array<string, mixed>
     */
    private function groupMeta(Collection $slots): array
    {
        $first = $slots->filter()->first();

        return [
            'label' => $first?->label,
            'starts_on' => $first?->starts_on?->format('Y-m-d'),
            'ends_on' => $first?->ends_on?->format('Y-m-d'),
            'is_active' => $first ? (bool) $first->is_active : true,
        ];
    }

    private function groupQuery(string $scope, ?int $cantonId, ?string $groupKey)
    {
        return Ranking::query()
            ->where('scope', $scope)
            ->when(
                $scope === RankingScope::CANTON_TOP3,
                fn ($q) => $q->where('canton_id', $cantonId),
                fn ($q) => $q->whereNull('canton_id')
            )
            ->when(
                $scope === RankingScope::GROUP_TOP,
                fn ($q) => $q->where('professional_group', $groupKey),
                function ($q) use ($scope, $groupKey) {
                    // National / hero: ignore group unless explicitly stored
                    if ($scope === RankingScope::NATIONAL_TOP5 || $scope === RankingScope::HERO_MATCH) {
                        return $q->where(function ($inner) {
                            $inner->whereNull('professional_group')->orWhere('professional_group', '');
                        });
                    }
                    if ($groupKey) {
                        return $q->where('professional_group', $groupKey);
                    }

                    return $q;
                }
            );
    }

    private function slotKind(string $scope): string
    {
        return match ($scope) {
            RankingScope::ARTICLE_TOP => 'article',
            RankingScope::EVENT_TOP => 'event',
            default => 'expert',
        };
    }

    /**
     * @return Collection<int, Expert>
     */
    private function expertOptions(): Collection
    {
        return Expert::query()
            ->with(['offices.canton'])
            ->orderByRaw("CASE status WHEN 'approved' THEN 0 WHEN 'pending' THEN 1 WHEN 'disabled' THEN 2 ELSE 3 END")
            ->orderBy('company_name')
            ->get([
                'id',
                'company_name',
                'professional_group',
                'status',
                'is_public',
                'contact_person_name',
                'contact_person_last_name',
                'email',
            ]);
    }
}
