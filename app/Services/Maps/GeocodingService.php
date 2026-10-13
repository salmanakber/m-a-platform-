<?php

namespace App\Services\Maps;

use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeocodingService
{
    public function __construct(private SettingsService $settings)
    {
    }

    /**
     * Resolve Swiss address parts to coordinates.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocodeSwissAddress(
        ?string $addressLine,
        ?string $postalCode,
        ?string $city,
        ?string $cantonCode = null
    ): ?array {
        $parts = array_filter([
            trim((string) $addressLine),
            trim(trim((string) $postalCode).' '.trim((string) $city)),
            $cantonCode ? strtoupper($cantonCode) : null,
            'Switzerland',
        ], fn ($p) => $p !== null && $p !== '');

        if ($parts === []) {
            return null;
        }

        $query = implode(', ', $parts);

        $google = $this->viaGoogle($query);
        if ($google !== null) {
            return $google;
        }

        return $this->viaNominatim($query);
    }

    /** @return array{latitude: float, longitude: float}|null */
    private function viaGoogle(string $query): ?array
    {
        $apiKey = $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY);
        if (blank($apiKey)) {
            return null;
        }

        try {
            $response = Http::timeout(12)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'region' => 'ch',
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json('results');
            if (! is_array($results) || $results === []) {
                return null;
            }

            $loc = $results[0]['geometry']['location'] ?? null;
            if (! is_array($loc) || ! isset($loc['lat'], $loc['lng'])) {
                return null;
            }

            return [
                'latitude' => (float) $loc['lat'],
                'longitude' => (float) $loc['lng'],
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{latitude: float, longitude: float}|null */
    private function viaNominatim(string $query): ?array
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'NachfolgeExperten/1.0 (map geocoding)',
                    'Accept-Language' => 'de',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'ch',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json();
            if (! is_array($results) || $results === [] || empty($results[0]['lat']) || empty($results[0]['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $results[0]['lat'],
                'longitude' => (float) $results[0]['lon'],
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
