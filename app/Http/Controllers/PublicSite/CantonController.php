<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Models\ExpertOffice;
use App\Support\ExpertStatus;
use Illuminate\View\View;

class CantonController extends Controller
{
    public function index(): View
    {
        $cantons = Canton::query()
            ->orderBy('code')
            ->get()
            ->map(function (Canton $canton) {
                $canton->experts_count = Expert::query()
                    ->where('status', ExpertStatus::APPROVED)
                    ->where('is_public', true)
                    ->whereHas('offices', fn ($o) => $o->where('canton_id', $canton->id))
                    ->count();

                return $canton;
            });

        $withExperts = $cantons->where('experts_count', '>', 0)->count();
        $totalExperts = (int) $cantons->sum('experts_count');
        $maxCount = max(1, (int) $cantons->max('experts_count'));

        return view('public.cantons.index', [
            'cantons' => $cantons,
            'withExperts' => $withExperts,
            'totalExperts' => $totalExperts,
            'maxCount' => $maxCount,
            'topCantons' => $cantons->sortByDesc('experts_count')->take(4)->values(),
        ]);
    }

    public function show(string $code): View
    {
        $canton = Canton::query()->where('code', strtoupper($code))->firstOrFail();

        $expertIds = ExpertOffice::query()
            ->where('canton_id', $canton->id)
            ->pluck('expert_id');

        $experts = Expert::query()
            ->whereIn('id', $expertIds)
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->with('offices.canton')
            ->orderBy('company_name')
            ->get();

        return view('public.cantons.show', [
            'canton' => $canton,
            'experts' => $experts,
        ]);
    }
}
