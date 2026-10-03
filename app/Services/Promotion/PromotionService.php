<?php

namespace App\Services\Promotion;

use App\Models\Promotion;
use App\Models\PromotionWaitlist;
use App\Support\InvoicePaymentStatus;
use App\Support\PromotionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Request a promotion slot or join the waitlist when occupied.
     */
    public function requestPosition(int $expertId, int $cantonId, int $positionNumber): Promotion|PromotionWaitlist
    {
        if ($positionNumber < 1 || $positionNumber > 3) {
            throw new \InvalidArgumentException('Position number must be between 1 and 3.');
        }

        return DB::transaction(function () use ($expertId, $cantonId, $positionNumber) {
            $active = Promotion::query()
                ->where('canton_id', $cantonId)
                ->where('position_number', $positionNumber)
                ->whereIn('status', [PromotionStatus::PENDING, PromotionStatus::ACTIVE])
                ->lockForUpdate()
                ->exists();

            if ($active) {
                return $this->joinWaitlist($expertId, $cantonId, $positionNumber);
            }

            return Promotion::query()->create([
                'expert_id' => $expertId,
                'canton_id' => $cantonId,
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
                'canton_id' => $next->canton_id,
                'position_number' => $promotion->position_number,
                'status' => PromotionStatus::PENDING,
            ]);

            $next->delete();
        });
    }
}
