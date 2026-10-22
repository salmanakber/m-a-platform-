<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Models\ExpertOffice;
use App\Services\Maps\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpertOfficeController extends Controller
{
    public function __construct(private GeocodingService $geocoder)
    {
    }

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        return response()->json([
            'suggestions' => $this->geocoder->suggest($validated['q'], 6, 'address'),
        ]);
    }

    public function store(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $this->validateOffice($request);
        $validated = $this->maybeGeocode($request, $validated);

        $maxOrder = (int) $expert->offices()->max('sort_order');
        $makePrimary = $request->boolean('is_primary') || $expert->offices()->count() === 0;

        $office = $expert->offices()->create(array_merge($validated, [
            'is_primary' => $makePrimary,
            'sort_order' => $maxOrder + 1,
        ]));

        if ($makePrimary) {
            $this->syncPrimary($expert->id, $office->id);
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', 'Standort hinzugefügt.');
    }

    public function update(Request $request, Expert $expert, ExpertOffice $office): RedirectResponse
    {
        $this->authorizeOffice($expert, $office);

        $validated = $this->validateOffice($request);
        $validated = $this->maybeGeocode($request, $validated, $office);

        $office->update(array_merge($validated, [
            'is_primary' => $request->boolean('is_primary'),
        ]));

        if ($request->boolean('is_primary')) {
            $this->syncPrimary($expert->id, $office->id);
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', 'Standort aktualisiert.');
    }

    public function destroy(Expert $expert, ExpertOffice $office): RedirectResponse
    {
        $this->authorizeOffice($expert, $office);

        $wasPrimary = $office->is_primary;
        $office->delete();

        if ($wasPrimary) {
            $next = $expert->offices()->orderBy('sort_order')->first();
            if ($next) {
                $this->syncPrimary($expert->id, $next->id);
            }
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', 'Standort gelöscht.');
    }

    private function authorizeOffice(Expert $expert, ExpertOffice $office): void
    {
        if ((int) $office->expert_id !== (int) $expert->id) {
            abort(404);
        }
    }

    /** @return array<string, mixed> */
    private function validateOffice(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'canton_id' => ['required', 'exists:cantons,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geocode' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $data['latitude'] = isset($data['latitude']) && $data['latitude'] !== ''
            ? (float) $data['latitude']
            : null;
        $data['longitude'] = isset($data['longitude']) && $data['longitude'] !== ''
            ? (float) $data['longitude']
            : null;

        unset($data['geocode'], $data['is_primary']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function maybeGeocode(Request $request, array $validated, ?ExpertOffice $existing = null): array
    {
        $wantsGeocode = $request->boolean('geocode')
            || ($validated['latitude'] === null || $validated['longitude'] === null);

        if (! $wantsGeocode) {
            return $validated;
        }

        if (
            ! $request->boolean('geocode')
            && $validated['latitude'] !== null
            && $validated['longitude'] !== null
        ) {
            return $validated;
        }

        $canton = Canton::query()->find($validated['canton_id']);
        $coords = $this->geocoder->geocodeSwissAddress(
            $validated['address_line'] ?? null,
            $validated['postal_code'] ?? null,
            $validated['city'] ?? null,
            $canton?->code
        );

        if ($coords === null && $canton && $canton->latitude !== null && $canton->longitude !== null) {
            $coords = [
                'latitude' => (float) $canton->latitude,
                'longitude' => (float) $canton->longitude,
            ];
        }

        if ($coords !== null) {
            $validated['latitude'] = $coords['latitude'];
            $validated['longitude'] = $coords['longitude'];
        } elseif ($existing) {
            $validated['latitude'] = $existing->latitude;
            $validated['longitude'] = $existing->longitude;
        }

        return $validated;
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
