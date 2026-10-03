<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Services\Lead\LeadService;
use App\Support\ExpertStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(private LeadService $leadService)
    {
    }

    public function store(Request $request, string $expertSlug): RedirectResponse
    {
        $expert = Expert::query()
            ->where('slug', $expertSlug)
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->firstOrFail();

        $validated = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'buy_sell_context' => ['nullable', 'in:buy,sell,both'],
        ]);

        $this->leadService->create(array_merge($validated, [
            'expert_id' => $expert->id,
        ]));

        return redirect()
            ->route('leads.thank-you', ['expertSlug' => $expertSlug])
            ->with('success', true);
    }

    public function thankYou(string $expertSlug): View
    {
        $expert = Expert::query()
            ->where('slug', $expertSlug)
            ->first();

        return view('public.leads.thank-you', [
            'expert' => $expert,
        ]);
    }
}
