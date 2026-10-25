<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Canton;
use App\Models\Event;
use App\Models\Expert;
use App\Services\Ranking\RankingService;
use App\Support\ArticleStatus;
use App\Support\EventStatus;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private RankingService $rankings)
    {
    }

    public function index(Request $request): View
    {
        $heroMatch = $this->rankings->heroMatch();
        $nationalTop = $this->rankings->nationalTop5();
        $articleTop = $this->rankings->articleTop3();

        $cantonCode = strtoupper(trim((string) $request->query('kanton', '')));
        $cantons = Canton::query()->orderBy('name_de')->get();
        $selectedCanton = $cantonCode !== ''
            ? $cantons->first(fn (Canton $c) => $c->code === $cantonCode)
            : ($cantons->firstWhere('code', 'ZH') ?? $cantons->first());

        $cantonTop = $selectedCanton
            ? $this->rankings->cantonTop3((int) $selectedCanton->id)
            : collect();

        $marktplatzTop = $this->rankings->nationalTop5(ProfessionalGroup::MARKTPLATZ);
        if ($marktplatzTop->isEmpty()) {
            // Fallback: show approved Marktplätze as card list (never on map).
            $marktplatzTop = Expert::query()
                ->where('status', ExpertStatus::APPROVED)
                ->where('is_public', true)
                ->where('professional_group', ProfessionalGroup::MARKTPLATZ)
                ->with('offices.canton')
                ->orderBy('company_name')
                ->limit(5)
                ->get()
                ->values()
                ->map(fn (Expert $e, int $i) => (object) [
                    'position' => $i + 1,
                    'expert' => $e,
                    'label' => null,
                ]);
        }

        $articleCarousel = Article::query()
            ->where('status', ArticleStatus::PUBLISHED)
            ->when($articleTop->isNotEmpty(), function ($q) use ($articleTop) {
                $ids = $articleTop->pluck('article_id')->filter()->all();
                if ($ids !== []) {
                    $q->whereNotIn('id', $ids);
                }
            })
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        $upcomingEvents = Event::query()
            ->where('status', EventStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('starts_on')->orWhereDate('starts_on', '>=', now()->toDateString());
            })
            ->with(['expert', 'canton'])
            ->orderByRaw('CASE WHEN is_top_event = 1 THEN 0 ELSE 1 END')
            ->orderBy('starts_on')
            ->limit(6)
            ->get();

        $stats = [
            'experts' => Expert::query()->where('status', ExpertStatus::APPROVED)->where('is_public', true)->count(),
            'deals' => (int) Expert::query()->where('status', ExpertStatus::APPROVED)->sum('public_deals_count'),
            'rating' => round((float) Expert::query()->whereNotNull('public_rating')->avg('public_rating'), 1),
        ];

        return view('public.home', [
            'heroMatch' => $heroMatch,
            'nationalTop' => $nationalTop,
            'cantons' => $cantons,
            'selectedCanton' => $selectedCanton,
            'cantonTop' => $cantonTop,
            'marktplatzTop' => $marktplatzTop,
            'articleTop' => $articleTop,
            'articleCarousel' => $articleCarousel,
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
            'groups' => ProfessionalGroup::labels(),
        ]);
    }
}
