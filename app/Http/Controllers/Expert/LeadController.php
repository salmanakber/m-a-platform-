<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Support\LeadStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        $expert = Auth::user()?->expert;

        $leads = $expert
            ? Lead::query()->where('expert_id', $expert->id)->with('canton')->orderByDesc('created_at')->paginate(20)
            : Lead::query()->whereRaw('1 = 0')->paginate(20);

        return view('expert.leads.index', [
            'leads' => $leads,
            'statuses' => LeadStatus::all(),
        ]);
    }

    public function show(Lead $lead): View
    {
        $expert = Auth::user()?->expert;

        if ($expert === null || (int) $lead->expert_id !== (int) $expert->id) {
            abort(403);
        }

        $lead->load(['canton', 'statusHistories']);

        return view('expert.leads.show', [
            'lead' => $lead,
            'statuses' => LeadStatus::all(),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $expert = Auth::user()?->expert;

        if ($expert === null || (int) $lead->expert_id !== (int) $expert->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', LeadStatus::all())],
        ]);

        $from = $lead->status;
        $to = $validated['status'];

        if ($from !== $to) {
            $lead->update(['status' => $to]);
            LeadStatusHistory::query()->create([
                'lead_id' => $lead->id,
                'from_status' => $from,
                'to_status' => $to,
            ]);
        }

        return back()->with('success', 'Status aktualisiert.');
    }
}
