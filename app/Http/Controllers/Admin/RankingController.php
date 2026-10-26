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
            ->paginate(40)
            ->withQueryString();

        return view('admin.rankings.index', [
            'rankings' => $rankings,
            'scope' => $scope,
            'scopes' => RankingScope::labels(),
            'groups' => ProfessionalGroup::labels(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView(null, $request->query('scope'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Ranking::query()->create($data);

        return redirect()
            ->route('admin.rankings.index', ['scope' => $data['scope']])
            ->with('success', 'Ranking gespeichert.');
    }

    public function edit(Request $request, Ranking $ranking): View
    {
        return $this->formView($ranking, $request->query('scope', $ranking->scope));
    }

    public function update(Request $request, Ranking $ranking): RedirectResponse
    {
        $data = $this->validated($request);
        $ranking->update($data);

        return redirect()
            ->route('admin.rankings.index', ['scope' => $data['scope']])
            ->with('success', 'Ranking aktualisiert.');
    }

    public function destroy(Ranking $ranking): RedirectResponse
    {
        $scope = $ranking->scope;
        $ranking->delete();

        return redirect()
            ->route('admin.rankings.index', ['scope' => $scope])
            ->with('success', 'Ranking gelöscht.');
    }

    private function formView(?Ranking $ranking, ?string $scope): View
    {
        $scope = $scope ?: RankingScope::NATIONAL_TOP5;

        return view('admin.rankings.form', [
            'ranking' => $ranking,
            'scope' => $scope,
            'scopes' => RankingScope::labels(),
            'groups' => ProfessionalGroup::labels(),
            'cantons' => Canton::query()->orderBy('name_de')->get(),
            'experts' => Expert::query()
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
                ]),
            'articles' => Article::query()
                ->where('status', ArticleStatus::PUBLISHED)
                ->orderByDesc('published_at')
                ->limit(200)
                ->get(['id', 'title']),
            'events' => Event::query()
                ->where('status', EventStatus::PUBLISHED)
                ->orderByDesc('starts_on')
                ->limit(200)
                ->get(['id', 'title', 'starts_on']),
            'maxPosition' => RankingScope::maxPosition($scope),
            'statusLabels' => [
                ExpertStatus::APPROVED => 'Freigegeben',
                ExpertStatus::PENDING => 'Ausstehend',
                ExpertStatus::DISABLED => 'Deaktiviert',
                ExpertStatus::REJECTED => 'Abgelehnt',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $scope = (string) $request->input('scope');

        $rules = [
            'scope' => ['required', Rule::in(RankingScope::all())],
            'professional_group' => ['nullable', Rule::in(ProfessionalGroup::all())],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'expert_id' => ['nullable', 'exists:experts,id'],
            'article_id' => ['nullable', 'exists:articles,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'position' => ['required', 'integer', 'min:1', 'max:10'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
            'label' => ['nullable', 'string', 'max:120'],
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['professional_group'] = $data['professional_group'] ?? null;
        $data['canton_id'] = $data['canton_id'] ?? null;
        $data['expert_id'] = $data['expert_id'] ?? null;
        $data['article_id'] = $data['article_id'] ?? null;
        $data['event_id'] = $data['event_id'] ?? null;

        if (in_array($scope, [RankingScope::HERO_MATCH, RankingScope::NATIONAL_TOP5, RankingScope::CANTON_TOP3, RankingScope::GROUP_TOP], true)
            && empty($data['expert_id'])) {
            throw ValidationException::withMessages(['expert_id' => 'Bitte einen Experten wählen.']);
        }

        if ($scope === RankingScope::CANTON_TOP3 && empty($data['canton_id'])) {
            throw ValidationException::withMessages(['canton_id' => 'Bitte einen Kanton wählen.']);
        }

        if ($scope === RankingScope::ARTICLE_TOP && empty($data['article_id'])) {
            throw ValidationException::withMessages(['article_id' => 'Bitte einen Artikel wählen.']);
        }

        if ($scope === RankingScope::EVENT_TOP && empty($data['event_id'])) {
            throw ValidationException::withMessages(['event_id' => 'Bitte ein Event wählen.']);
        }

        if ($scope === RankingScope::GROUP_TOP && empty($data['professional_group'])) {
            throw ValidationException::withMessages(['professional_group' => 'Bitte eine Fachgruppe wählen.']);
        }

        return $data;
    }
}
