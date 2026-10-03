<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Services\Email\NotificationService;
use App\Support\ExpertStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpertController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));
        $cantonId = $request->query('canton_id');

        $experts = Expert::query()
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('company_name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('contact_person_name', 'like', '%'.$q.'%')
                        ->orWhere('contact_person_last_name', 'like', '%'.$q.'%');
                });
            })
            ->when($cantonId, function ($query) use ($cantonId) {
                $query->whereHas('offices', fn ($o) => $o->where('canton_id', $cantonId));
            })
            ->with(['user', 'offices.canton'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.experts.index', [
            'experts' => $experts,
            'status' => $status,
            'q' => $q,
            'cantonId' => $cantonId,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
            'statuses' => ExpertStatus::all(),
        ]);
    }

    public function show(Expert $expert): View
    {
        $expert->load(['user', 'offices.canton', 'articles', 'promotions.canton']);

        return view('admin.experts.show', [
            'expert' => $expert,
        ]);
    }

    public function edit(Expert $expert): View
    {
        $expert->load('offices.canton');

        return view('admin.experts.edit', [
            'expert' => $expert,
        ]);
    }

    public function update(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'services_text' => ['nullable', 'string'],
            'offers_buy' => ['nullable', 'boolean'],
            'offers_sell' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'crawl_enabled' => ['nullable', 'boolean'],
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
            'contact_person_name' => ['nullable', 'string', 'max:120'],
            'contact_person_last_name' => ['nullable', 'string', 'max:120'],
        ]);

        $expert->update([
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'services_text' => $validated['services_text'] ?? null,
            'offers_buy' => $request->boolean('offers_buy'),
            'offers_sell' => $request->boolean('offers_sell'),
            'is_public' => $request->boolean('is_public'),
            'crawl_enabled' => $request->boolean('crawl_enabled'),
            'website_crawl_url' => $validated['website_crawl_url'] ?? null,
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'contact_person_last_name' => $validated['contact_person_last_name'] ?? null,
        ]);

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Experte aktualisiert.');
    }

    public function approve(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::APPROVED,
            'is_public' => true,
            'rejection_reason' => null,
        ]);

        try {
            $this->notifications->sendExpertRegistrationWelcome($expert->fresh());
        } catch (\Throwable) {
            // E-Mail optional when templates or Resend fehlen.
        }

        return back()->with('success', 'Experte freigegeben.');
    }

    public function reject(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $expert->update([
            'status' => ExpertStatus::REJECTED,
            'is_public' => false,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'Experte abgelehnt.');
    }

    public function deactivate(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::DISABLED,
            'is_public' => false,
        ]);

        return back()->with('success', 'Experte deaktiviert.');
    }

    public function activate(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::APPROVED,
            'is_public' => true,
        ]);

        return back()->with('success', 'Experte wieder aktiviert.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:experts,id'],
            'action' => ['required', 'in:approve,reject,deactivate'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $experts = Expert::query()->whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($experts as $expert) {
            if ($validated['action'] === 'approve') {
                $expert->update([
                    'status' => ExpertStatus::APPROVED,
                    'is_public' => true,
                    'rejection_reason' => null,
                ]);
                try {
                    $this->notifications->sendExpertRegistrationWelcome($expert->fresh());
                } catch (\Throwable) {
                }
            } elseif ($validated['action'] === 'reject') {
                $expert->update([
                    'status' => ExpertStatus::REJECTED,
                    'is_public' => false,
                    'rejection_reason' => $validated['rejection_reason'] ?? 'Bulk-Ablehnung',
                ]);
            } else {
                $expert->update([
                    'status' => ExpertStatus::DISABLED,
                    'is_public' => false,
                ]);
            }
            $count++;
        }

        $labels = [
            'approve' => 'freigegeben',
            'reject' => 'abgelehnt',
            'deactivate' => 'deaktiviert',
        ];

        return back()->with('success', $count.' Experte(n) '.$labels[$validated['action']].'.');
    }
}
