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
            return $this->active(RankingScope::GROUP_TOP, $group)->take(5);
        }

        return $this->active(RankingScope::NATIONAL_TOP5)->take(5);
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function cantonTop3(int $cantonId, ?string $group = null): Collection
    {
        return Ranking::query()
            ->currentlyActive()
            ->where('scope', RankingScope::CANTON_TOP3)
            ->where('canton_id', $cantonId)
            ->when($group, fn ($q) => $q->where('professional_group', $group))
            ->with(['expert.offices.canton', 'canton'])
            ->orderBy('position')
            ->take(3)
            ->get();
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function articleTop3(): Collection
    {
        return $this->active(RankingScope::ARTICLE_TOP)->take(3);
    }

    /**
     * @return Collection<int, Ranking>
     */
    public function eventTops(): Collection
    {
        return $this->active(RankingScope::EVENT_TOP);
    }
}
