<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\ExpertOffice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(): View
    {
        $expert = Auth::user()?->expert;

        return view('expert.offices.index', [
            'offices' => $expert?->offices()->with('canton')->orderBy('sort_order')->get() ?? collect(),
            'cantons' => Canton::query()->orderBy('name_de')->get(),
            'expert' => $expert,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $this->validateOffice($request);

        $maxOrder = (int) $expert->offices()->max('sort_order');

        $office = $expert->offices()->create(array_merge($validated, [
            'is_primary' => $request->boolean('is_primary'),
            'sort_order' => $maxOrder + 1,
        ]));

        if ($request->boolean('is_primary')) {
            $this->syncPrimary($expert->id, $office->id);
        }

        return back()->with('success', 'Standort hinzugefügt.');
    }

    public function update(Request $request, ExpertOffice $office): RedirectResponse
    {
        $expert = $this->requireExpert();
        $this->authorizeOffice($expert->id, $office);

        $validated = $this->validateOffice($request);

        $office->update(array_merge($validated, [
            'is_primary' => $request->boolean('is_primary'),
        ]));

        if ($request->boolean('is_primary')) {
            $this->syncPrimary($expert->id, $office->id);
        }

        return back()->with('success', 'Standort aktualisiert.');
    }

    public function destroy(ExpertOffice $office): RedirectResponse
    {
        $expert = $this->requireExpert();
        $this->authorizeOffice($expert->id, $office);

        $office->delete();

        return back()->with('success', 'Standort entfernt.');
    }

    private function requireExpert()
    {
        $expert = Auth::user()?->expert;

        if ($expert === null) {
            abort(403);
        }

        return $expert;
    }

    private function authorizeOffice(int $expertId, ExpertOffice $office): void
    {
        if ((int) $office->expert_id !== $expertId) {
            abort(403);
        }
    }

    /** @return array<string, mixed> */
    private function validateOffice(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'canton_id' => ['required', 'exists:cantons,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
    }

    private function syncPrimary(int $expertId, int $primaryOfficeId): void
    {
        ExpertOffice::query()
            ->where('expert_id', $expertId)
            ->where('id', '!=', $primaryOfficeId)
            ->update(['is_primary' => false]);

        ExpertOffice::query()->where('id', $primaryOfficeId)->update(['is_primary' => true]);
    }
}
