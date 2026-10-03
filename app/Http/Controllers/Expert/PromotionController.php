<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Promotion;
use App\Models\PromotionWaitlist;
use App\Services\Promotion\PromotionService;
use App\Services\Settings\SettingsService;
use App\Support\PromotionStatus;
use App\Support\SettingKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private PromotionService $promotionService,
        private SettingsService $settings
    ) {
    }

    public function index(): View
    {
        $expert = Auth::user()?->expert;

        $promotions = $expert
            ? Promotion::query()->where('expert_id', $expert->id)->with('canton')->orderByDesc('created_at')->get()
            : collect();

        $waitlist = $expert
            ? PromotionWaitlist::query()->where('expert_id', $expert->id)->with('canton')->orderBy('queue_order')->get()
            : collect();

        $cantons = Canton::query()->orderBy('name_de')->get();

        $occupied = Promotion::query()
            ->whereIn('status', [PromotionStatus::PENDING, PromotionStatus::ACTIVE])
            ->get()
            ->groupBy(fn ($p) => $p->canton_id.'-'.$p->position_number);

        $freeSlots = 0;
        $takenSlots = 0;
        foreach ($cantons as $canton) {
            for ($pos = 1; $pos <= 3; $pos++) {
                if ($occupied->has($canton->id.'-'.$pos)) {
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
            'occupied' => $occupied,
            'monthlyPrice' => $this->settings->get(SettingKey::PROMOTION_MONTHLY_PRICE_CHF, '0'),
            'expert' => $expert,
            'freeSlots' => $freeSlots,
            'takenSlots' => $takenSlots,
            'activeCount' => $promotions->where('status', PromotionStatus::ACTIVE)->count(),
            'pendingCount' => $promotions->where('status', PromotionStatus::PENDING)->count(),
        ]);
    }

    public function requestPromotion(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'canton_id' => ['required', 'exists:cantons,id'],
            'position_number' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        try {
            $result = $this->promotionService->requestPosition(
                $expert->id,
                (int) $validated['canton_id'],
                (int) $validated['position_number']
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result instanceof PromotionWaitlist) {
            return back()->with('success', 'Position belegt — Sie stehen auf der Warteliste.');
        }

        return back()->with('success', 'Promotion angefragt (Status: ausstehend).');
    }

    public function joinWaitlist(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'canton_id' => ['required', 'exists:cantons,id'],
            'position_number' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $this->promotionService->joinWaitlist(
            $expert->id,
            (int) $validated['canton_id'],
            isset($validated['position_number']) ? (int) $validated['position_number'] : null
        );

        return back()->with('success', 'Zur Warteliste hinzugefügt.');
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
