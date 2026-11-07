<?php

namespace App\Services\Ranking;

use App\Models\Ranking;
use App\Support\RankingScope;
use Illuminate\Support\Collection;

class RankingService
{
    /**
     * @return Collection<int, Ranking>
     */
    public function active(string $scope, ?string $group = null, ?int $cantonId = null): Collection
    {
        return Ranking::query()
            ->currentlyActive()
            ->where('scope', $scope)
            ->when($group !== null, fn ($q) => $q->where('professional_group', $group))
            ->when($cantonId !== null, fn ($q) => $q->where('canton_id', $cantonId))
            ->with(['expert.offices.canton', 'article', 'event.expert', 'event.canton', 'canton'])
            ->orderBy('position')
            ->get();
    }

    public function heroMatch(): ?Ranking
    {
        return $this->heroMatches()->first();
    }

    /**
     * Up to 3 experts for the homepage banner carousel.
     *
     * @return Collection<int, Ranking>
     */
    public function heroMatches(): Collection
    {
        return $this->active(RankingScope::HERO_MATCH)->take(3);
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function nationalTop5(?string $group = null): Collection
    {
        if ($group) {
            return $this->active(RankingScope::GROUP_TOP, $group)
                ->filter(fn (Ranking $row) => $row->expert_id && $row->expert)
                ->take(6)
                ->values();
        }

        return $this->active(RankingScope::NATIONAL_TOP5)
            ->filter(fn (Ranking $row) => $row->expert_id && $row->expert)
            ->take(6)
            ->values();
    }

    /** Default remark for open/empty ranking slots on the homepage. */
    public function nationalContactRemark(): ?string
    {
        return 'Platz frei — melden Sie sich bei uns für eine Top-Position auf nachfolge-experten.ch.';
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function cantonTop5(int $cantonId, ?string $group = null): Collection
    {
        return Ranking::query()
            ->currentlyActive()
            ->where('scope', RankingScope::CANTON_TOP3)
            ->where('canton_id', $cantonId)
            ->when($group, fn ($q) => $q->where('professional_group', $group))
            ->with(['expert.offices.canton', 'canton'])
            ->orderBy('position')
            ->get()
            ->filter(fn (Ranking $row) => $row->expert_id && $row->expert)
            ->take(6)
            ->values();
    }

    /** @deprecated Use cantonTop5() */
    public function cantonTop3(int $cantonId, ?string $group = null): Collection
    {
        return $this->cantonTop5($cantonId, $group);
    }

    /**
     * Global Top 3 Fachbeiträge (no category), or Top 3 within a Fachbereich group.
     *
     * @return Collection<int, Ranking>
     */
    public function articleTop3(?int $categoryId = null): Collection
    {
        // Name kept for compatibility; returns up to Top 6 Fachbeiträge.
        return Ranking::query()
            ->currentlyActive()
            ->where('scope', RankingScope::ARTICLE_TOP)
            ->when(
                $categoryId !== null,
                fn ($q) => $q->where('article_category_id', $categoryId),
                fn ($q) => $q->whereNull('article_category_id')
            )
            ->with(['article.category', 'article.expert', 'articleCategory'])
            ->orderBy('position')
            ->take(6)
            ->get();
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function eventTops(): Collection
    {
        return $this->active(RankingScope::EVENT_TOP);
    }
}
