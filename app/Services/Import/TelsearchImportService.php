<?php

namespace App\Services\Import;

use App\Models\Canton;
use App\Models\Expert;
use App\Models\ImportRun;
use App\Models\User;
use App\Services\Logo\LogoDiscoveryService;
use App\Services\Maps\GeocodingService;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
use App\Support\Role;
use App\Support\SwissPlz;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class TelsearchImportService
{
    public const IMPORT_PATH = 'imports/Basis_telsearch.xlsx';

    public const TEMPLATE_PATH = 'imports/template_experts.csv';

    public function __construct(private GeocodingService $geocoder)
    {
    }

    /** @var array<string, list<string>> */
    private const COLUMN_ALIASES = [
        'company_name' => ['firma', 'firmenname', 'unternehmen', 'company', 'company_name', 'name', 'gesellschaft', 'betrieb'],
        'email' => ['email', 'e-mail', 'e_mail', 'mail', 'emailadresse'],
        'phone' => ['telefon', 'tel', 'phone', 'handy', 'mobil', 'telephone'],
        'website' => ['website', 'web', 'url', 'homepage', 'webseite', 'internet', 'www', 'link'],
        'logo_url' => ['logo', 'logo_url', 'logo_link', 'firmenlogo', 'bild_url', 'image_url'],
        'address_line' => ['adresse', 'address', 'strasse', 'straße', 'street', 'address_line', 'anschrift', 'str'],
        'postal_code' => ['plz', 'postal_code', 'postleitzahl', 'zip', 'zipcode'],
        'city' => ['ort', 'city', 'stadt', 'place', 'wohnort'],
        'canton' => ['kanton', 'canton', 'kt', 'canton_code', 'kantonscode'],
        'full_address' => ['volladresse', 'full_address', 'adresse_komplett', 'anschrift_komplett', 'standort'],
        'contact_person_name' => ['vorname', 'contact_person_name', 'first_name', 'kontakt_vorname', 'ansprechpartner_vorname'],
        'contact_person_last_name' => ['nachname', 'contact_person_last_name', 'last_name', 'kontakt_nachname', 'ansprechpartner_nachname', 'name_kontakt'],
        'contact_person_full' => ['ansprechperson', 'ansprechpartner', 'kontaktperson', 'kontakt', 'contact', 'contact_person'],
        'description' => ['beschreibung', 'description', 'taetigkeit', 'tätigkeit', 'branche', 'industry', 'bemerkung'],
        'offers_buy' => ['kauf', 'buy', 'kaufen', 'offers_buy', 'ma_kauf'],
        'offers_sell' => ['verkauf', 'sell', 'verkaufen', 'nachfolge', 'offers_sell', 'ma_verkauf'],
        'professional_group' => ['gruppe', 'group', 'professional_group', 'kategorie', 'category', 'fachgruppe', 'berufsgruppe'],
        'latitude' => ['lat', 'latitude', 'breitengrad'],
        'longitude' => ['lng', 'lon', 'long', 'longitude', 'laengengrad', 'längengrad'],
    ];

    /**
     * @return array{
     *     run: ImportRun,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     failed: int,
     *     geocoded: int
     * }
     */
    public function importFromUpload(?UploadedFile $file, ?int $userId = null, bool $geocode = true): array
    {
        $path = self::IMPORT_PATH;
        $filename = basename($path);

        if ($file) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
            $filename = $file->getClientOriginalName();
            $path = 'imports/upload_'.now()->format('Ymd_His').'.'.$ext;
            Storage::disk('local')->putFileAs('imports', $file, basename($path));
        }

        return $this->importPath($path, $filename, $userId, $geocode);
    }

    /**
     * @return array{
     *     run: ImportRun,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     failed: int,
     *     geocoded: int
     * }
     */
    public function importPath(string $storagePath, ?string $filename = null, ?int $userId = null, bool $geocode = true): array
    {
        if (! Storage::disk('local')->exists($storagePath)) {
            throw new \RuntimeException('Import-Datei nicht gefunden: storage/app/'.$storagePath);
        }

        $absolute = Storage::disk('local')->path($storagePath);
        $run = ImportRun::query()->create([
            'user_id' => $userId,
            'source' => 'telsearch',
            'filename' => $filename ?: basename($storagePath),
            'storage_path' => $storagePath,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $created = $updated = $skipped = $failed = $geocoded = 0;
        $errors = [];

        try {
            $rows = $this->readSpreadsheet($absolute);
            $run->update(['rows_total' => count($rows)]);

            $cantonsByCode = Canton::query()->get()->keyBy(fn (Canton $c) => strtoupper($c->code));
            $cantonsByName = Canton::query()->get()->keyBy(fn (Canton $c) => $this->normalizeKey($c->name_de));

            foreach ($rows as $index => $row) {
                $line = $index + 2; // header is row 1
                try {
                    $mapped = $this->mapRow($row);
                    if (($mapped['company_name'] ?? '') === '') {
                        $skipped++;
                        if (count($errors) < 80) {
                            $errors[] = [
                                'row' => $line,
                                'message' => 'Übersprungen: Firmenname fehlt (Spalte Firma/Name nicht erkannt oder leer).',
                            ];
                        }
                        continue;
                    }

                    $result = $this->upsertExpert($mapped, $cantonsByCode, $cantonsByName, $geocode);
                    if ($result['status'] === 'created') {
                        $created++;
                    } elseif ($result['status'] === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    if (! empty($result['geocoded'])) {
                        $geocoded++;
                    }
                    if (! empty($result['warnings'])) {
                        foreach ($result['warnings'] as $warning) {
                            if (count($errors) < 80) {
                                $errors[] = [
                                    'row' => $line,
                                    'message' => $warning,
                                ];
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $failed++;
                    if (count($errors) < 80) {
                        $errors[] = [
                            'row' => $line,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }

            $run->update([
                'status' => 'completed',
                'rows_created' => $created,
                'rows_updated' => $updated,
                'rows_skipped' => $skipped,
                'rows_failed' => $failed,
                'errors' => $errors,
                'meta' => [
                    'mapped_fields' => array_keys(self::COLUMN_ALIASES),
                    'geocoded' => $geocoded,
                    'notes' => 'Adressen werden geparst (PLZ/Ort/Kanton), Standorte als ExpertOffice gespeichert und per Geocoding auf die Karte gesetzt. Fehlender Kanton wird per PLZ abgeleitet.',
                ],
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'rows_created' => $created,
                'rows_updated' => $updated,
                'rows_skipped' => $skipped,
                'rows_failed' => $failed,
                'errors' => array_merge($errors, [['row' => null, 'message' => $e->getMessage()]]),
                'finished_at' => now(),
            ]);

            throw $e;
        }

        return compact('run', 'created', 'updated', 'skipped', 'failed', 'geocoded');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readRows(?string $storagePath = null): array
    {
        $storagePath = $storagePath ?: self::IMPORT_PATH;
        if (! Storage::disk('local')->exists($storagePath)) {
            throw new \RuntimeException('Import-Datei nicht gefunden: storage/app/'.$storagePath);
        }

        return $this->readSpreadsheet(Storage::disk('local')->path($storagePath));
    }

    public function ensureTemplate(): string
    {
        if (! Storage::disk('local')->exists(self::TEMPLATE_PATH)) {
            $csv = implode(',', [
                'Firma',
                'E-Mail',
                'Telefon',
                'Website',
                'Adresse',
                'PLZ',
                'Ort',
                'Kanton',
                'Vorname',
                'Nachname',
                'Kauf',
                'Verkauf',
                'Beschreibung',
                'Gruppe',
                'Logo-URL',
            ])."\n";
            $csv .= '"Beispiel M&A AG","info@beispiel.ch","+41 44 000 00 00","https://beispiel.ch","Musterstrasse 1","8001","Zürich","ZH","Max","Muster","ja","ja","Unternehmensnachfolge und Kaufberatung","M&A Specialists",""'."\n";
            $csv .= '"KMU Beratungen GmbH","info@kmuberatungen.ch","+41 41 000 00 00","https://www.kmuberatungen.ch","Brünigstrasse 144","6060","Sarnen","OW","Beispiel","Kontakt","ja","ja","Nachfolge und M&A","M&A Specialists",""'."\n";
            Storage::disk('local')->put(self::TEMPLATE_PATH, $csv);
        }

        return Storage::disk('local')->path(self::TEMPLATE_PATH);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readSpreadsheet(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($absolutePath)->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);

        if ($matrix === []) {
            return [];
        }

        $headerRow = array_shift($matrix);
        $headers = [];
        foreach ($headerRow as $i => $header) {
            $headers[$i] = $this->normalizeKey((string) $header);
        }

        $rows = [];
        foreach ($matrix as $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = isset($row[$i]) ? $this->cleanCell($row[$i]) : '';
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $out = [];
        foreach (self::COLUMN_ALIASES as $field => $aliases) {
            $out[$field] = $this->pick($row, $aliases);
        }

        $out['company_name'] = $this->cleanCell($out['company_name']);
        $out['email'] = strtolower($this->cleanCell($out['email']));
        $out['website'] = $this->normalizeWebsite((string) $out['website']);
        $out['logo_url'] = $this->normalizeWebsite((string) $out['logo_url']);
        $out['offers_buy'] = $this->toBool($out['offers_buy']);
        $out['offers_sell'] = $this->toBool($out['offers_sell']);
        $out['professional_group'] = $this->normalizeProfessionalGroup((string) $out['professional_group']);

        // Telsearch "Ansprechperson" is often a full name in one column.
        if (($out['contact_person_name'] === '' || $out['contact_person_last_name'] === '')
            && ($out['contact_person_full'] ?? '') !== '') {
            $parts = preg_split('/\s+/u', trim((string) $out['contact_person_full'])) ?: [];
            if (count($parts) === 1) {
                if ($out['contact_person_name'] === '') {
                    $out['contact_person_name'] = $parts[0];
                }
            } elseif (count($parts) > 1) {
                if ($out['contact_person_last_name'] === '') {
                    $out['contact_person_last_name'] = array_pop($parts);
                }
                if ($out['contact_person_name'] === '') {
                    $out['contact_person_name'] = implode(' ', $parts);
                }
            }
        }

        $this->enrichAddressFields($out);

        if (! $out['offers_buy'] && ! $out['offers_sell']) {
            // Default: both true when spreadsheet has no buy/sell flags
            $out['offers_buy'] = true;
            $out['offers_sell'] = true;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @param  \Illuminate\Support\Collection<string, Canton>  $cantonsByCode
     * @param  \Illuminate\Support\Collection<string, Canton>  $cantonsByName
     * @return array{status: string, warnings: list<string>, geocoded?: bool}
     */
    private function upsertExpert(array $mapped, $cantonsByCode, $cantonsByName, bool $geocode = true): array
    {
        $fingerprint = $this->fingerprint($mapped);
        $email = $mapped['email'] !== ''
            ? $mapped['email']
            : 'import+'.substr($fingerprint, 0, 16).'@nachfolge-experten.local';

        $canton = $this->resolveCanton((string) $mapped['canton'], $cantonsByCode, $cantonsByName);
        if (! $canton && $mapped['postal_code'] !== '') {
            $fromPlz = SwissPlz::cantonCode((string) $mapped['postal_code']);
            if ($fromPlz && $cantonsByCode->has($fromPlz)) {
                $canton = $cantonsByCode->get($fromPlz);
            }
        }

        return DB::transaction(function () use ($mapped, $fingerprint, $email, $canton, $geocode) {
            $warnings = [];
            $geocoded = false;
            $existing = Expert::query()->where('import_fingerprint', $fingerprint)->first();

            if (! $existing && ! str_ends_with($email, '@nachfolge-experten.local')) {
                $existing = Expert::query()->where('email', $email)->first();
            }

            // Re-imports after address-parsing improvements may change the fingerprint.
            // Fall back to same imported company name to avoid duplicates and heal missing offices.
            if (! $existing) {
                $existing = Expert::query()
                    ->where('imported_from', 'telsearch')
                    ->where('company_name', $mapped['company_name'])
                    ->orderBy('id')
                    ->first();
            }

            if ($existing) {
                $existing->fill([
                    'company_name' => $mapped['company_name'],
                    'website' => $mapped['website'] !== '' ? $mapped['website'] : $existing->website,
                    'website_crawl_url' => $mapped['website'] !== '' ? $mapped['website'] : $existing->website_crawl_url,
                    'email' => str_ends_with($email, '@nachfolge-experten.local') ? $existing->email : $email,
                    'phone' => $mapped['phone'] !== '' ? $mapped['phone'] : $existing->phone,
                    'description' => $mapped['description'] !== '' ? $mapped['description'] : $existing->description,
                    'offers_buy' => $mapped['offers_buy'],
                    'offers_sell' => $mapped['offers_sell'],
                    'professional_group' => $mapped['professional_group'] ?: $existing->professional_group,
                    'contact_person_name' => $mapped['contact_person_name'] !== '' ? $mapped['contact_person_name'] : $existing->contact_person_name,
                    'contact_person_last_name' => $mapped['contact_person_last_name'] !== '' ? $mapped['contact_person_last_name'] : $existing->contact_person_last_name,
                    'imported_from' => 'telsearch',
                    'import_fingerprint' => $fingerprint,
                ])->save();

                $officeResult = $this->upsertOffice($existing, $mapped, $canton, $geocode);
                if ($officeResult['warning']) {
                    $warnings[] = $officeResult['warning'];
                }
                $geocoded = $officeResult['geocoded'];
                $this->maybeImportLogo($existing, (string) $mapped['logo_url']);

                return ['status' => 'updated', 'warnings' => $warnings, 'geocoded' => $geocoded];
            }

            $expert = Expert::query()->create([
                'user_id' => $this->importOwnerUserId(),
                'company_name' => $mapped['company_name'],
                'slug' => $this->uniqueSlug($mapped['company_name']),
                'website' => $mapped['website'] !== '' ? $mapped['website'] : null,
                'website_crawl_url' => $mapped['website'] !== '' ? $mapped['website'] : null,
                'email' => $email,
                'phone' => $mapped['phone'] !== '' ? $mapped['phone'] : null,
                'description' => $mapped['description'] !== '' ? $mapped['description'] : null,
                'offers_buy' => $mapped['offers_buy'],
                'offers_sell' => $mapped['offers_sell'],
                'professional_group' => $mapped['professional_group'] ?: ProfessionalGroup::MA_SPECIALIST,
                'status' => ExpertStatus::PENDING,
                'is_public' => false,
                'contact_person_name' => $mapped['contact_person_name'] !== '' ? $mapped['contact_person_name'] : null,
                'contact_person_last_name' => $mapped['contact_person_last_name'] !== '' ? $mapped['contact_person_last_name'] : null,
                'imported_from' => 'telsearch',
                'import_fingerprint' => $fingerprint,
            ]);

            $officeResult = $this->upsertOffice($expert, $mapped, $canton, $geocode);
            if ($officeResult['warning']) {
                $warnings[] = $officeResult['warning'];
            }
            $geocoded = $officeResult['geocoded'];
            $this->maybeImportLogo($expert, (string) $mapped['logo_url']);

            return ['status' => 'created', 'warnings' => $warnings, 'geocoded' => $geocoded];
        });
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @return array{warning: ?string, geocoded: bool}
     */
    private function upsertOffice(Expert $expert, array $mapped, ?Canton $canton, bool $geocode = true): array
    {
        $hasAddressBits = $mapped['address_line'] !== ''
            || $mapped['postal_code'] !== ''
            || $mapped['city'] !== '';

        if (! $canton) {
            if ($hasAddressBits) {
                return [
                    'warning' => 'Hinweis: Adresse vorhanden, aber Kanton nicht erkannt — Standort nicht gespeichert (Profil trotzdem importiert). PLZ/Kanton prüfen und erneut importieren.',
                    'geocoded' => false,
                ];
            }

            return [
                'warning' => 'Hinweis: Kein Kanton/Standort in der Zeile — Experte ohne Adresse angelegt.',
                'geocoded' => false,
            ];
        }

        $office = $expert->offices()->where('is_primary', true)->first()
            ?? $expert->offices()->first();

        $lat = is_numeric($mapped['latitude'] ?? null) ? (float) $mapped['latitude'] : null;
        $lng = is_numeric($mapped['longitude'] ?? null) ? (float) $mapped['longitude'] : null;
        $geocoded = false;

        $needsGeocode = $geocode
            && $hasAddressBits
            && ($lat === null || $lng === null || $this->coordsLookLikeCantonCenter($lat, $lng, $canton, $office));

        if ($needsGeocode) {
            try {
                $coords = $this->geocoder->geocodeSwissAddress(
                    $mapped['address_line'] !== '' ? $mapped['address_line'] : null,
                    $mapped['postal_code'] !== '' ? $mapped['postal_code'] : null,
                    $mapped['city'] !== '' ? $mapped['city'] : null,
                    $canton->code
                );
                if ($coords !== null) {
                    $lat = $coords['latitude'];
                    $lng = $coords['longitude'];
                    $geocoded = true;
                    // Be kind to Nominatim rate limits when Google is unavailable.
                    usleep(200000);
                }
            } catch (Throwable $e) {
                Log::warning('Import geocode failed for '.$expert->company_name.': '.$e->getMessage());
            }
        }

        if ($lat === null || $lng === null) {
            $lat = $canton->latitude !== null ? (float) $canton->latitude : null;
            $lng = $canton->longitude !== null ? (float) $canton->longitude : null;
        }

        $payload = [
            'label' => 'Hauptsitz',
            'address_line' => $mapped['address_line'] !== '' ? $mapped['address_line'] : '—',
            'postal_code' => $mapped['postal_code'] !== '' ? $mapped['postal_code'] : '0000',
            'city' => $mapped['city'] !== '' ? $mapped['city'] : ($canton->name_de ?? 'Schweiz'),
            'canton_id' => $canton->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'is_primary' => true,
            'sort_order' => 0,
        ];

        if ($office) {
            $office->fill($payload)->save();
        } else {
            $expert->offices()->create($payload);
        }

        return ['warning' => null, 'geocoded' => $geocoded];
    }

    private function coordsLookLikeCantonCenter(?float $lat, ?float $lng, Canton $canton, $office = null): bool
    {
        if ($lat === null || $lng === null || $canton->latitude === null || $canton->longitude === null) {
            // Existing office already parked on canton center → refresh via geocode.
            if ($office && $office->latitude !== null && $office->longitude !== null
                && $canton->latitude !== null && $canton->longitude !== null) {
                return abs((float) $office->latitude - (float) $canton->latitude) < 0.0001
                    && abs((float) $office->longitude - (float) $canton->longitude) < 0.0001;
            }

            return true;
        }

        return abs($lat - (float) $canton->latitude) < 0.0001
            && abs($lng - (float) $canton->longitude) < 0.0001;
    }

    private function cleanCell(mixed $value): string
    {
        $text = (string) $value;
        // Excel/Telsearch often uses non-breaking spaces.
        $text = preg_replace('/[\x{00A0}\x{202F}\x{2007}\x{2009}]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function maybeImportLogo(Expert $expert, string $logoUrl): void
    {
        if ($logoUrl === '' || $expert->logo_locked) {
            return;
        }

        try {
            app(LogoDiscoveryService::class)->storeFromUrl($expert, $logoUrl);
        } catch (Throwable) {
            // Logo is optional — never fail the import row.
        }
    }

    /**
     * Parse combined address blobs used by Telsearch exports, e.g.:
     * - "Brünigstrasse 144, 6060 Sarnen"
     * - ort column "5600 Lenzburg AG" (PLZ + city + canton)
     *
     * @param  array<string, mixed>  $out
     */
    private function enrichAddressFields(array &$out): void
    {
        foreach (['full_address', 'address_line', 'postal_code', 'city', 'canton'] as $field) {
            if (isset($out[$field])) {
                $out[$field] = $this->cleanCell($out[$field]);
            }
        }

        $blob = (string) ($out['full_address'] ?? '');
        if ($blob === '' && $out['address_line'] !== '' && $out['postal_code'] === '' && $out['city'] === '') {
            $blob = (string) $out['address_line'];
        }

        if ($blob !== '' && preg_match('/^(.*?),\s*(\d{4})\s+(.+)$/u', $blob, $m)) {
            if ($out['address_line'] === '' || $out['address_line'] === $blob) {
                $out['address_line'] = trim($m[1]);
            }
            if ($out['postal_code'] === '') {
                $out['postal_code'] = $m[2];
            }
            if ($out['city'] === '') {
                $out['city'] = trim($m[3]);
            }
        }

        // Telsearch "Ort" often embeds PLZ + city + optional canton: "5600 Lenzburg AG"
        $cityRaw = (string) $out['city'];
        if ($cityRaw !== '' && preg_match('/^(\d{4})\s+(.+?)(?:\s+([A-Za-z]{2}))?$/u', $cityRaw, $m)) {
            if ($out['postal_code'] === '') {
                $out['postal_code'] = $m[1];
            }
            $out['city'] = trim($m[2]);
            if ($out['canton'] === '' && ! empty($m[3])) {
                $out['canton'] = strtoupper($m[3]);
            }
        }

        // "6060 Sarnen" leftover in city
        if ($out['postal_code'] === '' && preg_match('/\b(\d{4})\b/', (string) $out['city'], $m)) {
            $out['postal_code'] = $m[1];
            $out['city'] = trim(preg_replace('/\b'.$m[1].'\b/', '', (string) $out['city']) ?? (string) $out['city']);
        }

        if ($out['postal_code'] === '' && preg_match('/\b(\d{4})\b/', (string) $out['address_line'], $m)) {
            $out['postal_code'] = $m[1];
        }

        // Trailing canton code on city when PLZ already separate: "Lenzburg AG"
        if ($out['canton'] === '' && preg_match('/\s+([A-Za-z]{2})$/u', (string) $out['city'], $m)) {
            $maybe = strtoupper($m[1]);
            if (strlen($maybe) === 2) {
                $out['canton'] = $maybe;
                $out['city'] = trim(substr((string) $out['city'], 0, -strlen($m[0])));
            }
        }

        // Strip trailing canton from city again after PLZ split (e.g. "Muri b. Bern BE")
        if ($out['canton'] !== '' && preg_match('/\s+'.preg_quote($out['canton'], '/').'$/iu', (string) $out['city'])) {
            $out['city'] = trim(preg_replace('/\s+'.preg_quote($out['canton'], '/').'$/iu', '', (string) $out['city']) ?? (string) $out['city']);
        }

        $out['address_line'] = $this->cleanCell($out['address_line']);
        $out['postal_code'] = $this->cleanCell($out['postal_code']);
        $out['city'] = $this->cleanCell($out['city']);
        $out['canton'] = $this->cleanCell($out['canton']);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Canton>  $cantonsByCode
     * @param  \Illuminate\Support\Collection<string, Canton>  $cantonsByName
     */
    private function resolveCanton(string $value, $cantonsByCode, $cantonsByName): ?Canton
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', $value) ?? '');
        if (strlen($code) === 2 && $cantonsByCode->has($code)) {
            return $cantonsByCode->get($code);
        }

        $key = $this->normalizeKey($value);

        return $cantonsByName->get($key);
    }

    private function normalizeProfessionalGroup(string $value): string
    {
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return ProfessionalGroup::MA_SPECIALIST;
        }

        return match (true) {
            str_contains($key, 'jurist') || str_contains($key, 'anwalt') || str_contains($key, 'recht') => ProfessionalGroup::JURIST,
            str_contains($key, 'marktplatz') || str_contains($key, 'marketplace') || str_contains($key, 'plattform') => ProfessionalGroup::MARKTPLATZ,
            str_contains($key, 'other') || str_contains($key, 'andere') || str_contains($key, 'sonstig') => ProfessionalGroup::OTHER,
            default => ProfessionalGroup::MA_SPECIALIST,
        };
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function fingerprint(array $mapped): string
    {
        $raw = implode('|', [
            $this->normalizeKey($mapped['company_name']),
            $this->normalizeKey($mapped['postal_code']),
            $this->normalizeKey($mapped['city']),
            strtolower((string) $mapped['email']),
        ]);

        return hash('sha256', $raw);
    }

    private function importOwnerUserId(): int
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'import@nachfolge-experten.local'],
            [
                'name' => 'Telsearch Import',
                'password' => Hash::make(Str::random(40)),
                'role' => Role::EXPERT,
                'is_active' => false,
            ]
        );

        return (int) $user->id;
    }

    private function uniqueSlug(string $company): string
    {
        $base = Str::slug($company);
        if ($base === '') {
            $base = 'experte';
        }
        $slug = $base;
        $i = 2;
        while (Expert::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $aliases
     */
    private function pick(array $row, array $aliases): string
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeKey($alias);
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private function normalizeKey(string $value): string
    {
        $value = Str::ascii(mb_strtolower(trim($value)));
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], mb_strtolower(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function normalizeWebsite(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strcasecmp($url, 'n/a') === 0 || $url === '-' || $url === '—') {
            return '';
        }

        $url = preg_replace('/\s+/', '', $url) ?? $url;
        $url = preg_replace('#^www\.#i', 'https://www.', $url) ?? $url;

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host']) || ! str_contains($parts['host'], '.')) {
            return '';
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $normalized = $scheme.'://'.$parts['host'];
        if (! empty($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }
        if (! empty($parts['path']) && $parts['path'] !== '/') {
            $normalized .= rtrim($parts['path'], '/');
        }

        return $normalized;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return false;
        }

        return in_array($v, ['1', 'true', 'yes', 'ja', 'y', 'x', 'oui', 'on'], true);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
