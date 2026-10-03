<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Support\LeadStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $expertId = $request->query('expert_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $leads = Lead::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($expertId, fn ($q) => $q->where('expert_id', $expertId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->with(['expert', 'canton'])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'status' => $status,
            'expertId' => $expertId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => LeadStatus::all(),
            'experts' => Expert::query()->orderBy('company_name')->get(['id', 'company_name']),
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['expert', 'canton', 'statusHistories']);

        return view('admin.leads.show', [
            'lead' => $lead,
            'statuses' => LeadStatus::all(),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
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
