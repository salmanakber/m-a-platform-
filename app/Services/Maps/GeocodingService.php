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
     * Address autocomplete suggestions for Switzerland.
     *
     * @return list<array{
     *     label: string,
     *     address_line: string|null,
     *     postal_code: string|null,
     *     city: string|null,
     *     canton_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null
     * }>
     */
    /**
     * @param  string|null  $types  Google Places Autocomplete types, e.g. "address", "geocode", or null for all
     */
    public function suggest(string $query, int $limit = 6, ?string $types = 'geocode'): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $limit = max(1, min(8, $limit));

        $google = $this->suggestViaGoogle($query, $limit, $types);
        if ($google !== []) {
            return $google;
        }

        return $this->suggestViaNominatim($query, $limit);
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

    /**
     * @return list<array<string, mixed>>
     */
    private function suggestViaGoogle(string $query, int $limit, ?string $types = 'geocode'): array
    {
        $apiKey = $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY);
        if (blank($apiKey)) {
            return [];
        }

        try {
            $params = [
                'input' => $query,
                'components' => 'country:ch',
                'language' => 'de',
                'key' => $apiKey,
            ];
            if ($types !== null && $types !== '') {
                $params['types'] = $types;
            }

            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);

            if (! $response->successful()) {
                return [];
            }

            $predictions = $response->json('predictions');
            if (! is_array($predictions) || $predictions === []) {
                return [];
            }

            $out = [];
            foreach (array_slice($predictions, 0, $limit) as $prediction) {
                $placeId = $prediction['place_id'] ?? null;
                $description = (string) ($prediction['description'] ?? '');
                if (! $placeId || $description === '') {
                    continue;
                }

                $details = $this->googlePlaceDetails((string) $placeId, (string) $apiKey);
                if ($details === null) {
                    $out[] = [
                        'label' => $description,
                        'address_line' => $description,
                        'postal_code' => null,
                        'city' => null,
                        'canton_code' => null,
                        'latitude' => null,
                        'longitude' => null,
                    ];
                    continue;
                }

                $out[] = $details;
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    private function googlePlaceDetails(string $placeId, string $apiKey): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'formatted_address,address_component,geometry',
                'language' => 'de',
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json('result');
            if (! is_array($result)) {
                return null;
            }

            return $this->normalizeComponents(
                (string) ($result['formatted_address'] ?? ''),
                is_array($result['address_components'] ?? null) ? $result['address_components'] : [],
                isset($result['geometry']['location']['lat']) ? (float) $result['geometry']['location']['lat'] : null,
                isset($result['geometry']['location']['lng']) ? (float) $result['geometry']['location']['lng'] : null,
                'google'
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function suggestViaNominatim(string $query, int $limit): array
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'NachfolgeExperten/1.0 (address suggest)',
                    'Accept-Language' => 'de',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => $limit,
                    'countrycodes' => 'ch',
                ]);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json();
            if (! is_array($results)) {
                return [];
            }

            $out = [];
            foreach ($results as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $address = is_array($row['address'] ?? null) ? $row['address'] : [];
                $normalized = $this->normalizeNominatim(
                    (string) ($row['display_name'] ?? ''),
                    $address,
                    isset($row['lat']) ? (float) $row['lat'] : null,
                    isset($row['lon']) ? (float) $row['lon'] : null
                );
                if ($normalized !== null) {
                    $out[] = $normalized;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return array<string, mixed>
     */
    private function normalizeComponents(
        string $label,
        array $components,
        ?float $lat,
        ?float $lng,
        string $source
    ): array {
        $map = [];
        foreach ($components as $component) {
            $types = $component['types'] ?? [];
            if (! is_array($types)) {
                continue;
            }
            foreach ($types as $type) {
                $map[(string) $type] = (string) ($component['long_name'] ?? $component['short_name'] ?? '');
                if ($type === 'administrative_area_level_1') {
                    $map['canton_short'] = (string) ($component['short_name'] ?? '');
                }
            }
        }

        $street = trim(($map['route'] ?? '').' '.($map['street_number'] ?? ''));
        if ($street === '') {
            $street = $map['premise'] ?? null;
        }

        $canton = $map['canton_short'] ?? null;
        if ($canton && strlen($canton) > 2) {
            $canton = $this->guessCantonCode($canton);
        }

        return [
            'label' => $label !== '' ? $label : trim(($street ?? '').', '.($map['postal_code'] ?? '').' '.($map['locality'] ?? '')),
            'address_line' => $street !== '' && $street !== null ? $street : null,
            'postal_code' => $map['postal_code'] ?? null,
            'city' => $map['locality'] ?? ($map['postal_town'] ?? ($map['sublocality'] ?? null)),
            'canton_code' => $canton ? strtoupper(substr($canton, 0, 2)) : null,
            'latitude' => $lat,
            'longitude' => $lng,
            'source' => $source,
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>|null
     */
    private function normalizeNominatim(string $label, array $address, ?float $lat, ?float $lng): ?array
    {
        $street = trim(($address['road'] ?? $address['pedestrian'] ?? '').' '.($address['house_number'] ?? ''));
        $city = $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? null;
        $postal = $address['postcode'] ?? null;
        $state = $address['state'] ?? $address['county'] ?? null;
        $canton = $this->guessCantonCode(is_string($state) ? $state : null);

        if ($street === '' && $city === null && $postal === null) {
            return null;
        }

        return [
            'label' => $label,
            'address_line' => $street !== '' ? $street : null,
            'postal_code' => $postal,
            'city' => $city,
            'canton_code' => $canton,
            'latitude' => $lat,
            'longitude' => $lng,
            'source' => 'nominatim',
        ];
    }

    private function guessCantonCode(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $raw = trim($name);
        if (preg_match('/^[A-Za-z]{2}$/', $raw)) {
            return strtoupper($raw);
        }

        $key = mb_strtolower($raw);
        $map = [
            'aargau' => 'AG',
            'appenzell innerrhoden' => 'AI',
            'appenzell ausserrhoden' => 'AR',
            'bern' => 'BE',
            'berne' => 'BE',
            'basel-landschaft' => 'BL',
            'basel-stadt' => 'BS',
            'freiburg' => 'FR',
            'fribourg' => 'FR',
            'genf' => 'GE',
            'genève' => 'GE',
            'geneva' => 'GE',
            'glarus' => 'GL',
            'graubünden' => 'GR',
            'grisons' => 'GR',
            'jura' => 'JU',
            'luzern' => 'LU',
            'lucerne' => 'LU',
            'neuenburg' => 'NE',
            'neuchâtel' => 'NE',
            'nidwalden' => 'NW',
            'obwalden' => 'OW',
            'st. gallen' => 'SG',
            'saint-gall' => 'SG',
            'schaffhausen' => 'SH',
            'solothurn' => 'SO',
            'schwyz' => 'SZ',
            'thurgau' => 'TG',
            'tessin' => 'TI',
            'ticino' => 'TI',
            'uri' => 'UR',
            'waadt' => 'VD',
            'vaud' => 'VD',
            'wallis' => 'VS',
            'valais' => 'VS',
            'zug' => 'ZG',
            'zürich' => 'ZH',
            'zurich' => 'ZH',
        ];

        return $map[$key] ?? null;
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
