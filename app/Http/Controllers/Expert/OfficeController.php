<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\ExpertOffice;
use App\Services\Maps\GeocodingService;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function __construct(
        private GeocodingService $geocoder,
        private SettingsService $settings
    ) {
    }

    public function index(): View
    {
        $expert = Auth::user()?->expert;
        $cantons = Canton::query()->orderBy('name_de')->get();

        return view('expert.offices.index', [
            'offices' => $expert?->offices()->with('canton')->orderByDesc('is_primary')->orderBy('sort_order')->get() ?? collect(),
            'cantons' => $cantons,
            'cantonCodeMap' => $cantons->pluck('id', 'code'),
            'expert' => $expert,
            'suggestUrl' => route('expert.offices.suggest'),
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $this->requireExpert();

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        return response()->json([
            'suggestions' => $this->geocoder->suggest($validated['q'], 6, 'address'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

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

        $msg = 'Standort gespeichert.';
        if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
            $msg = 'Standort gespeichert und auf der Karte positioniert.';
        } elseif ($request->boolean('geocode')) {
            $msg = 'Standort gespeichert — Koordinaten konnten nicht ermittelt werden. Bitte Adresse prüfen oder Lat/Lng manuell eintragen.';
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, ExpertOffice $office): RedirectResponse
    {
        $expert = $this->requireExpert();
        $this->authorizeOffice($expert->id, $office);

        $validated = $this->validateOffice($request);
        $validated = $this->maybeGeocode($request, $validated, $office);

        $office->update(array_merge($validated, [
            'is_primary' => $request->boolean('is_primary'),
        ]));

        if ($request->boolean('is_primary')) {
            $this->syncPrimary($expert->id, $office->id);
        }

        $msg = 'Standort aktualisiert.';
        if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
            $msg = 'Standort aktualisiert und auf der Karte positioniert.';
        }

        return back()->with('success', $msg);
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

        // Keep manual coords unless geocode checkbox is on or coords are empty.
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
            // Soft fallback: canton center so the office still appears on the map.
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
