<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Models\Canton;
use App\Models\Promotion;
use App\Models\PromotionWaitlist;
use App\Services\Promotion\PromotionService;
use App\Support\ProfessionalGroup;
use App\Support\PromotionStatus;
use App\Support\RankingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotionService)
    {
    }

    public function index(Request $request): View
    {
        $expert = Auth::user()?->expert;

        $promotions = $expert
            ? Promotion::query()
                ->where('expert_id', $expert->id)
                ->with(['canton', 'articleCategory'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $waitlist = $expert
            ? PromotionWaitlist::query()->where('expert_id', $expert->id)->with('canton')->orderBy('queue_order')->get()
            : collect();

        $group = $expert?->professional_group ?: ProfessionalGroup::MA_SPECIALIST;
        $tabs = $this->availableTabs($group);
        $activeTab = (string) $request->query('scope', RankingScope::CANTON_TOP3);
        if (! isset($tabs[$activeTab])) {
            $activeTab = array_key_first($tabs) ?: RankingScope::CANTON_TOP3;
        }

        $occupied = Promotion::query()
            ->whereIn('status', [PromotionStatus::PENDING, PromotionStatus::ACTIVE])
            ->get()
            ->keyBy(fn (Promotion $p) => $p->occupancyKey());

        $cantons = Canton::query()->orderBy('name_de')->get();
        $articleCategories = ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get();

        $priceMaps = [];
        foreach (array_keys($tabs) as $scope) {
            $priceMaps[$scope] = $this->promotionService->priceMap(
                $scope,
                $scope === RankingScope::GROUP_TOP ? $group : null
            );
        }

        $orderTotal = 0.0;
        foreach ($promotions->whereIn('status', [PromotionStatus::PENDING, PromotionStatus::ACTIVE]) as $promo) {
            $orderTotal += $this->promotionService->priceFor(
                $promo->scope ?: RankingScope::CANTON_TOP3,
                (int) $promo->position_number,
                $promo->professional_group
            );
        }

        $freeSlots = 0;
        $takenSlots = 0;
        foreach ($cantons as $canton) {
            for ($pos = 1; $pos <= 6; $pos++) {
                $key = implode(':', [RankingScope::CANTON_TOP3, '', (string) $canton->id, '', (string) $pos]);
                if ($occupied->has($key)) {
                    $takenSlots++;
                } else {
                    $freeSlots++;
                }
            }
        }

        return view('expert.promotions.index', [
            'promotions' => $promotions,
            'waitlist' => $waitlist,
            'cantons' => $cantons,
            'articleCategories' => $articleCategories,
            'occupied' => $occupied,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'priceMaps' => $priceMaps,
            'orderTotal' => $orderTotal,
            'expert' => $expert,
            'expertGroup' => $group,
            'freeSlots' => $freeSlots,
            'takenSlots' => $takenSlots,
            'activeCount' => $promotions->where('status', PromotionStatus::ACTIVE)->count(),
            'pendingCount' => $promotions->where('status', PromotionStatus::PENDING)->count(),
            'selectedCantonCode' => $request->query('kanton'),
        ]);
    }

    public function requestPromotion(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'scope' => ['required', 'string'],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'position_number' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $scope = $validated['scope'];
        $group = $expert->professional_group ?: ProfessionalGroup::MA_SPECIALIST;

        try {
            $result = $this->promotionService->requestPosition(
                $expert->id,
                $scope,
                (int) $validated['position_number'],
                isset($validated['canton_id']) ? (int) $validated['canton_id'] : null,
                isset($validated['article_category_id']) ? (int) $validated['article_category_id'] : null,
                $scope === RankingScope::GROUP_TOP ? $group : null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result instanceof PromotionWaitlist) {
            return back()->with('success', 'Position belegt — Sie stehen auf der Warteliste.');
        }

        $price = $this->promotionService->priceFor(
            $scope,
            (int) $validated['position_number'],
            $scope === RankingScope::GROUP_TOP ? $group : null
        );

        return redirect()
            ->route('expert.promotions.index', ['scope' => $scope])
            ->with('success', 'Promotion angefragt (Status: ausstehend) · CHF '.number_format($price, 2, '.', "'").'/Mt.');
    }

    public function joinWaitlist(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'canton_id' => ['required', 'exists:cantons,id'],
            'position_number' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $this->promotionService->joinWaitlist(
            $expert->id,
            (int) $validated['canton_id'],
            isset($validated['position_number']) ? (int) $validated['position_number'] : null
        );

        return back()->with('success', 'Zur Warteliste hinzugefügt.');
    }

    /** @return array<string, array{label: string, lead: string}> */
    private function availableTabs(string $group): array
    {
        $tabs = [
            RankingScope::CANTON_TOP3 => [
                'label' => 'Top 6 Kanton',
                'lead' => 'Regionale Top-Platzierungen in einzelnen Kantonen (auch für Juristen & Treuhand).',
            ],
        ];

        if ($group === ProfessionalGroup::MA_SPECIALIST) {
            $tabs[RankingScope::NATIONAL_TOP5] = [
                'label' => 'Top 6 Schweiz · Berater',
                'lead' => 'Nationale Top-Auswahl auf der Startseite (Berater).',
            ];
        } else {
            $tabs[RankingScope::GROUP_TOP] = [
                'label' => 'Top 6 '.ProfessionalGroup::label($group),
                'lead' => 'Nationale Top-Auswahl Ihrer Fachgruppe auf der Startseite.',
            ];
        }

        $tabs[RankingScope::HERO_MATCH] = [
            'label' => 'Top empfohlen',
            'lead' => 'Banner «Top Match» auf der Startseite (bis 3 Plätze).',
        ];

        $tabs[RankingScope::ARTICLE_TOP] = [
            'label' => 'Fachbeiträge',
            'lead' => 'Top-Platzierungen für Fachbeiträge je Fachbereich.',
        ];

        return $tabs;
    }

    private function requireExpert()
    {
        $expert = Auth::user()?->expert;

        if ($expert === null) {
            abort(403);
        }

        return $expert;
    }
}
