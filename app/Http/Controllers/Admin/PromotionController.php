<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\Promotion\PromotionService;
use App\Support\InvoicePaymentStatus;
use App\Support\PromotionStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotionService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $promotions = Promotion::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['expert', 'canton', 'invoice'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'status' => $status,
            'statuses' => PromotionStatus::all(),
        ]);
    }

    public function activate(Promotion $promotion): RedirectResponse
    {
        $promotion->load('invoice');

        try {
            if ($promotion->invoice !== null && $promotion->invoice->payment_status === InvoicePaymentStatus::PAID) {
                $this->promotionService->activateAfterPayment($promotion, Carbon::now(), 1);
            } else {
                $startsAt = now();
                $promotion->update([
                    'status' => PromotionStatus::ACTIVE,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMonth(),
                ]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Aktivierung fehlgeschlagen: '.$e->getMessage());
        }

        return back()->with('success', 'Promotion aktiviert.');
    }

    public function expire(Promotion $promotion): RedirectResponse
    {
        $this->promotionService->expirePromotion($promotion);

        return back()->with('success', 'Promotion beendet.');
    }
}
