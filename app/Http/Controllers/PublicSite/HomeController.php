<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Canton;
use App\Models\Event;
use App\Models\Expert;
use App\Models\Ranking;
use App\Services\Ranking\RankingService;
use App\Support\ArticleStatus;
use App\Support\EventStatus;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private RankingService $rankings)
    {
    }

    public function index(Request $request): View
    {
        $heroMatches = $this->rankings->heroMatches();
        $heroMatch = $heroMatches->first();
        $nationalTop = $this->rankings->nationalTop5();
        $articleTopRankings = $this->rankings->articleTop3();

        $cantonCode = strtoupper(trim((string) $request->query('kanton', '')));
        $cantons = Canton::query()->orderBy('name_de')->get();
        $selectedCanton = $cantonCode !== ''
            ? $cantons->first(fn (Canton $c) => $c->code === $cantonCode)
            : ($cantons->firstWhere('code', 'ZH') ?? $cantons->first());

        $cantonTop = $selectedCanton
            ? $this->rankings->cantonTop3((int) $selectedCanton->id)
            : collect();

        $countsByCanton = Expert::query()
            ->selectRaw('expert_offices.canton_id, COUNT(DISTINCT experts.id) as experts_count')
            ->join('expert_offices', 'expert_offices.expert_id', '=', 'experts.id')
            ->where('experts.status', ExpertStatus::APPROVED)
            ->where('experts.is_public', true)
            ->where('experts.professional_group', '!=', ProfessionalGroup::MARKTPLATZ)
            ->groupBy('expert_offices.canton_id')
            ->pluck('experts_count', 'canton_id');

        $cantonTiles = $cantons->map(fn (Canton $canton) => [
            'code' => $canton->code,
            'name' => $canton->name_de,
            'count' => (int) ($countsByCanton[$canton->id] ?? 0),
        ]);

        $marktplatzRankings = $this->rankings->nationalTop5(ProfessionalGroup::MARKTPLATZ);
        if ($marktplatzRankings->isEmpty()) {
            $marktplatzRankings = Expert::query()
                ->where('status', ExpertStatus::APPROVED)
                ->where('is_public', true)
                ->where('professional_group', ProfessionalGroup::MARKTPLATZ)
                ->with('offices.canton')
                ->orderBy('company_name')
                ->limit(5)
                ->get()
                ->values()
                ->map(function (Expert $expert, int $index) {
                    $row = new Ranking(['position' => $index + 1]);
                    $row->setRelation('expert', $expert);

                    return $row;
                });
        }

        $articleTopIds = $articleTopRankings->pluck('article_id')->filter()->all();
        $articleCarousel = Article::query()
            ->where('status', ArticleStatus::PUBLISHED)
            ->when($articleTopIds !== [], fn ($q) => $q->whereNotIn('id', $articleTopIds))
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        $upcomingEvents = Event::query()
            ->where('status', EventStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('starts_on')->orWhereDate('starts_on', '>=', now()->toDateString());
            })
            ->with(['expert', 'canton'])
            ->orderByRaw('CASE WHEN is_top_event = 1 THEN 0 ELSE 1 END')
            ->orderBy('starts_on')
            ->limit(8)
            ->get();

        $approvedPublic = Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true);

        $stats = [
            'experts' => (clone $approvedPublic)->count(),
            'deals' => (int) (clone $approvedPublic)->sum('public_deals_count'),
            'rating' => round((float) Expert::query()->whereNotNull('public_rating')->avg('public_rating'), 1),
        ];

        $periodLabel = $heroMatch && $heroMatch->label
            ? $heroMatch->label
            : ($nationalTop->first() && $nationalTop->first()->label
                ? $nationalTop->first()->label
                : now()->format('m.Y'));

        $nationalCards = $this->expertCardsFromRankings($nationalTop);
        $bannerCards = $this->expertCardsFromRankings($heroMatches)->take(3)->values();
        $heroCard = $bannerCards->first();

        return view('public.home', [
            'heroMatch' => $heroMatch,
            'heroCard' => $heroCard,
            'bannerCards' => $bannerCards,
            'nationalCards' => $nationalCards,
            'marktplatzCards' => $this->expertCardsFromRankings($marktplatzRankings),
            'cantonCards' => $this->expertCardsFromRankings($cantonTop),
            'articleTopCards' => $this->articleCardsFromRankings($articleTopRankings),
            'articleCarouselCards' => $articleCarousel->values()->map(function (Article $article, int $i) {
                return [
                    'n' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'title' => $article->title,
                    'url' => route('blog.show', $article->slug),
                    'excerpt' => Str::limit(strip_tags((string) ($article->excerpt ?: $article->body)), 100),
                ];
            }),
            'upcomingEvents' => $upcomingEvents,
            'cantons' => $cantons,
            'cantonTiles' => $cantonTiles,
            'selectedCanton' => $selectedCanton,
            'stats' => $stats,
            'periodLabel' => $periodLabel,
            'groups' => ProfessionalGroup::labels(),
        ]);
    }

    /**
     * @param  Collection<int, Ranking>  $rankings
     * @return Collection<int, array<string, mixed>>
     */
    private function expertCardsFromRankings(Collection $rankings): Collection
    {
        return $rankings
            ->filter(fn (Ranking $row) => $row->expert !== null)
            ->values()
            ->map(fn (Ranking $row) => $this->expertCard($row->expert, (int) $row->position, $row->label))
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expertCard(?Expert $expert, int $position = 1, ?string $label = null): ?array
    {
        if ($expert === null) {
            return null;
        }

        $office = $expert->primaryOffice();
        $first = trim((string) ($expert->contact_person_name ?: ''));
        $last = trim((string) ($expert->contact_person_last_name ?: ''));
        $displayName = trim($first.' '.$last);
        if ($displayName === '') {
            $displayName = $expert->company_name;
        }

        $initials = mb_strtoupper(
            mb_substr($first !== '' ? $first : $expert->company_name, 0, 1)
            .mb_substr($last !== '' ? $last : $expert->company_name, 0, 1)
        );
        if (mb_strlen($initials) < 2) {
            $initials = mb_strtoupper(mb_substr($expert->company_name, 0, 2));
        }

        $group = $expert->professional_group ?: ProfessionalGroup::MA_SPECIALIST;

        return [
            'position' => $position,
            'n' => str_pad((string) $position, 2, '0', STR_PAD_LEFT),
            'label' => $label,
            'display_name' => $displayName,
            'company' => $expert->company_name,
            'slug' => $expert->slug,
            'url' => route('experts.show', $expert->slug),
            'initials' => $initials,
            'group' => $group,
            'group_label' => ProfessionalGroup::label($group),
            'color' => ProfessionalGroup::color($group),
            'offers_buy' => (bool) $expert->offers_buy,
            'offers_sell' => (bool) $expert->offers_sell,
            'rating' => $expert->public_rating !== null ? number_format((float) $expert->public_rating, 1) : null,
            'deals' => $expert->public_deals_count,
            'city' => $office ? $office->city : null,
            'canton_code' => ($office && $office->canton) ? $office->canton->code : null,
            'place_line' => $office
                ? trim($office->city.($office->canton ? ' · Kanton '.$office->canton->code : ''))
                : 'Schweiz',
            'logo' => $expert->logo_path ? asset('storage/'.$expert->logo_path) : null,
        ];
    }

    /**
     * @param  Collection<int, Ranking>  $rankings
     * @return Collection<int, array<string, mixed>>
     */
    private function articleCardsFromRankings(Collection $rankings): Collection
    {
        return $rankings
            ->filter(fn (Ranking $row) => $row->article !== null)
            ->values()
            ->map(function (Ranking $row) {
                $article = $row->article;

                return [
                    'n' => str_pad((string) $row->position, 2, '0', STR_PAD_LEFT),
                    'title' => $article->title,
                    'url' => route('blog.show', $article->slug),
                    'excerpt' => Str::limit(strip_tags((string) ($article->excerpt ?: $article->body)), 120),
                    'date' => optional($article->published_at)->format('d.m.Y'),
                ];
            });
    }
}
