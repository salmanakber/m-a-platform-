<?php

namespace App\Services\Promotion;

use App\Models\Promotion;
use App\Models\PromotionPrice;
use App\Models\PromotionWaitlist;
use App\Support\InvoicePaymentStatus;
use App\Support\PromotionStatus;
use App\Support\RankingScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Request a promotion slot or join the waitlist when occupied.
     */
    public function requestPosition(
        int $expertId,
        string $scope,
        int $positionNumber,
        ?int $cantonId = null,
        ?int $articleCategoryId = null,
        ?string $professionalGroup = null
    ): Promotion|PromotionWaitlist {
        if (! in_array($scope, [
            RankingScope::CANTON_TOP3,
            RankingScope::NATIONAL_TOP5,
            RankingScope::GROUP_TOP,
            RankingScope::ARTICLE_TOP,
            RankingScope::HERO_MATCH,
        ], true)) {
            throw new \InvalidArgumentException('Unbekannter Promotion-Scope.');
        }

        $max = RankingScope::maxPosition($scope);
        if ($positionNumber < 1 || $positionNumber > $max) {
            throw new \InvalidArgumentException("Position muss zwischen 1 und {$max} liegen.");
        }

        if ($scope === RankingScope::CANTON_TOP3 && ! $cantonId) {
            throw new \InvalidArgumentException('Kanton ist erforderlich.');
        }

        if ($scope === RankingScope::ARTICLE_TOP && ! $articleCategoryId) {
            throw new \InvalidArgumentException('Fachbereich ist erforderlich.');
        }

        if ($scope !== RankingScope::CANTON_TOP3) {
            $cantonId = null;
        }

        if ($scope !== RankingScope::ARTICLE_TOP) {
            $articleCategoryId = null;
        }

        if ($scope !== RankingScope::GROUP_TOP) {
            $professionalGroup = $scope === RankingScope::NATIONAL_TOP5 ? null : $professionalGroup;
        }

        return DB::transaction(function () use ($expertId, $scope, $positionNumber, $cantonId, $articleCategoryId, $professionalGroup) {
            $active = Promotion::query()
                ->where('scope', $scope)
                ->where('position_number', $positionNumber)
                ->when(
                    $scope === RankingScope::CANTON_TOP3,
                    fn ($q) => $q->where('canton_id', $cantonId),
                    fn ($q) => $q->whereNull('canton_id')
                )
                ->when(
                    $scope === RankingScope::ARTICLE_TOP,
                    fn ($q) => $q->where('article_category_id', $articleCategoryId),
                    fn ($q) => $q->whereNull('article_category_id')
                )
                ->when(
                    $scope === RankingScope::GROUP_TOP,
                    fn ($q) => $q->where('professional_group', $professionalGroup),
                    fn ($q) => $q->whereNull('professional_group')
                )
                ->whereIn('status', [PromotionStatus::PENDING, PromotionStatus::ACTIVE])
                ->lockForUpdate()
                ->exists();

            if ($active) {
                if ($scope === RankingScope::CANTON_TOP3 && $cantonId) {
                    return $this->joinWaitlist($expertId, $cantonId, $positionNumber);
                }

                throw new \InvalidArgumentException('Dieser Platz ist bereits belegt. Bitte wählen Sie eine andere Position.');
            }

            return Promotion::query()->create([
                'expert_id' => $expertId,
                'scope' => $scope,
                'professional_group' => $professionalGroup,
                'canton_id' => $cantonId,
                'article_category_id' => $articleCategoryId,
                'position_number' => $positionNumber,
                'status' => PromotionStatus::PENDING,
            ]);
        });
    }

    public function joinWaitlist(int $expertId, int $cantonId, ?int $positionNumber = null): PromotionWaitlist
    {
        $maxOrder = PromotionWaitlist::query()
            ->where('canton_id', $cantonId)
            ->when($positionNumber !== null, fn ($q) => $q->where('position_number', $positionNumber))
            ->max('queue_order');

        return PromotionWaitlist::query()->create([
            'expert_id' => $expertId,
            'canton_id' => $cantonId,
            'position_number' => $positionNumber,
            'queue_order' => (int) $maxOrder + 1,
        ]);
    }

    public function priceFor(string $scope, int $position, ?string $professionalGroup = null): float
    {
        $query = PromotionPrice::query()
            ->where('scope', $scope)
            ->where('position', $position);

        $row = (clone $query)
            ->when(
                $professionalGroup,
                fn ($q) => $q->where('professional_group', $professionalGroup),
                fn ($q) => $q->whereNull('professional_group')
            )
            ->first();

        if ($row === null && $professionalGroup) {
            $row = $query->whereNull('professional_group')->first();
        }

        return $row ? (float) $row->price_chf : 0.0;
    }

    /** @return array<int, float> */
    public function priceMap(string $scope, ?string $professionalGroup = null): array
    {
        $max = RankingScope::maxPosition($scope);
        $map = [];
        for ($i = 1; $i <= $max; $i++) {
            $map[$i] = $this->priceFor($scope, $i, $professionalGroup);
        }

        return $map;
    }

    public function activateAfterPayment(Promotion $promotion, ?Carbon $startsAt = null, int $months = 1): Promotion
    {
        $promotion->loadMissing('invoice');

        if ($promotion->invoice === null) {
            throw new \RuntimeException('Promotion cannot be activated without an invoice.');
        }

        if ($promotion->invoice->payment_status !== InvoicePaymentStatus::PAID) {
            throw new \RuntimeException('Invoice must be marked as paid before activation.');
        }

        $startsAt = $startsAt ?? now();

        $promotion->update([
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonths($months),
        ]);

        return $promotion->fresh();
    }

    public function expireDuePromotions(): int
    {
        $expired = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expired as $promotion) {
            $this->expirePromotion($promotion);
        }

        return $expired->count();
    }

    public function expirePromotion(Promotion $promotion): void
    {
        DB::transaction(function () use ($promotion) {
            $promotion->update(['status' => PromotionStatus::EXPIRED]);
            $this->promoteNextFromWaitlist($promotion);
        });
    }

    public function cancelPromotion(Promotion $promotion): void
    {
        DB::transaction(function () use ($promotion) {
            $promotion->update([
                'status' => PromotionStatus::CANCELLED,
                'ends_at' => now(),
            ]);
            $this->promoteNextFromWaitlist($promotion);
        });
    }

    private function promoteNextFromWaitlist(Promotion $promotion): void
    {
        if (($promotion->scope ?: RankingScope::CANTON_TOP3) !== RankingScope::CANTON_TOP3 || ! $promotion->canton_id) {
            return;
        }

        $next = PromotionWaitlist::query()
            ->where('canton_id', $promotion->canton_id)
            ->where(function ($query) use ($promotion) {
                $query->whereNull('position_number')
                    ->orWhere('position_number', $promotion->position_number);
            })
            ->orderBy('queue_order')
            ->lockForUpdate()
            ->first();

        if ($next === null) {
            return;
        }

        Promotion::query()->create([
            'expert_id' => $next->expert_id,
            'scope' => RankingScope::CANTON_TOP3,
            'canton_id' => $next->canton_id,
            'position_number' => $promotion->position_number,
            'status' => PromotionStatus::PENDING,
        ]);

        $next->delete();
    }
}
