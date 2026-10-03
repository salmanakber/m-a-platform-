<?php

namespace App\Services\Import;

use App\Models\Canton;
use App\Models\Expert;
use App\Models\ImportRun;
use App\Models\User;
use App\Support\ExpertStatus;
use App\Support\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class TelsearchImportService
{
    public const IMPORT_PATH = 'imports/Basis_telsearch.xlsx';

    public const TEMPLATE_PATH = 'imports/template_experts.csv';

    /** @var array<string, list<string>> */
    private const COLUMN_ALIASES = [
        'company_name' => ['firma', 'firmenname', 'unternehmen', 'company', 'company_name', 'name', 'gesellschaft', 'betrieb'],
        'email' => ['email', 'e-mail', 'e_mail', 'mail', 'emailadresse'],
        'phone' => ['telefon', 'tel', 'phone', 'handy', 'mobil', 'telephone'],
        'website' => ['website', 'web', 'url', 'homepage', 'webseite', 'internet'],
        'address_line' => ['adresse', 'address', 'strasse', 'straße', 'street', 'address_line', 'anschrift'],
        'postal_code' => ['plz', 'postal_code', 'postleitzahl', 'zip', 'zipcode'],
        'city' => ['ort', 'city', 'stadt', 'place', 'wohnort'],
        'canton' => ['kanton', 'canton', 'kt', 'canton_code', 'kantonscode'],
        'contact_person_name' => ['vorname', 'contact_person_name', 'first_name', 'kontakt_vorname', 'ansprechpartner_vorname'],
        'contact_person_last_name' => ['nachname', 'contact_person_last_name', 'last_name', 'kontakt_nachname', 'ansprechpartner_nachname', 'name_kontakt'],
        'description' => ['beschreibung', 'description', 'taetigkeit', 'tätigkeit', 'branche', 'industry', 'bemerkung'],
        'offers_buy' => ['kauf', 'buy', 'kaufen', 'offers_buy', 'ma_kauf'],
        'offers_sell' => ['verkauf', 'sell', 'verkaufen', 'nachfolge', 'offers_sell', 'ma_verkauf'],
        'latitude' => ['lat', 'latitude', 'breitengrad'],
        'longitude' => ['lng', 'lon', 'long', 'longitude', 'laengengrad', 'längengrad'],
    ];

    /**
     * @return array{
     *     run: ImportRun,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     failed: int
     * }
     */
    public function importFromUpload(?UploadedFile $file, ?int $userId = null): array
    {
        $path = self::IMPORT_PATH;
        $filename = basename($path);

        if ($file) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
            $filename = $file->getClientOriginalName();
            $path = 'imports/upload_'.now()->format('Ymd_His').'.'.$ext;
            Storage::disk('local')->putFileAs('imports', $file, basename($path));
        }

        return $this->importPath($path, $filename, $userId);
    }

    /**
     * @return array{
     *     run: ImportRun,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     failed: int
     * }
     */
    public function importPath(string $storagePath, ?string $filename = null, ?int $userId = null): array
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

        $created = $updated = $skipped = $failed = 0;
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
                        continue;
                    }

                    $result = $this->upsertExpert($mapped, $cantonsByCode, $cantonsByName);
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $e) {
                    $failed++;
                    if (count($errors) < 50) {
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

        return compact('run', 'created', 'updated', 'skipped', 'failed');
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
            ])."\n";
            $csv .= '"Beispiel M&A AG","info@beispiel.ch","+41 44 000 00 00","https://beispiel.ch","Musterstrasse 1","8001","Zürich","ZH","Max","Muster","ja","ja","Unternehmensnachfolge und Kaufberatung"'."\n";
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
                $assoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
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

        $out['company_name'] = trim((string) $out['company_name']);
        $out['email'] = strtolower(trim((string) $out['email']));
        $out['website'] = $this->normalizeWebsite((string) $out['website']);
        $out['offers_buy'] = $this->toBool($out['offers_buy']);
        $out['offers_sell'] = $this->toBool($out['offers_sell']);

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
     */
    private function upsertExpert(array $mapped, $cantonsByCode, $cantonsByName): string
    {
        $fingerprint = $this->fingerprint($mapped);
        $email = $mapped['email'] !== ''
            ? $mapped['email']
            : 'import+'.substr($fingerprint, 0, 16).'@nachfolge-experten.local';

        $canton = $this->resolveCanton((string) $mapped['canton'], $cantonsByCode, $cantonsByName);

        return DB::transaction(function () use ($mapped, $fingerprint, $email, $canton) {
            $existing = Expert::query()->where('import_fingerprint', $fingerprint)->first();

            if (! $existing && ! str_ends_with($email, '@nachfolge-experten.local')) {
                $existing = Expert::query()->where('email', $email)->first();
            }

            if ($existing) {
                $existing->fill([
                    'company_name' => $mapped['company_name'],
                    'website' => $mapped['website'] ?: $existing->website,
                    'email' => str_ends_with($email, '@nachfolge-experten.local') ? $existing->email : $email,
                    'phone' => $mapped['phone'] !== '' ? $mapped['phone'] : $existing->phone,
                    'description' => $mapped['description'] !== '' ? $mapped['description'] : $existing->description,
                    'offers_buy' => $mapped['offers_buy'],
                    'offers_sell' => $mapped['offers_sell'],
                    'contact_person_name' => $mapped['contact_person_name'] !== '' ? $mapped['contact_person_name'] : $existing->contact_person_name,
                    'contact_person_last_name' => $mapped['contact_person_last_name'] !== '' ? $mapped['contact_person_last_name'] : $existing->contact_person_last_name,
                    'imported_from' => 'telsearch',
                    'import_fingerprint' => $fingerprint,
                ])->save();

                $this->upsertOffice($existing, $mapped, $canton);

                return 'updated';
            }

            $expert = Expert::query()->create([
                'user_id' => $this->importOwnerUserId(),
                'company_name' => $mapped['company_name'],
                'slug' => $this->uniqueSlug($mapped['company_name']),
                'website' => $mapped['website'] ?: null,
                'email' => $email,
                'phone' => $mapped['phone'] !== '' ? $mapped['phone'] : null,
                'description' => $mapped['description'] !== '' ? $mapped['description'] : null,
                'offers_buy' => $mapped['offers_buy'],
                'offers_sell' => $mapped['offers_sell'],
                'status' => ExpertStatus::PENDING,
                'is_public' => false,
                'contact_person_name' => $mapped['contact_person_name'] !== '' ? $mapped['contact_person_name'] : null,
                'contact_person_last_name' => $mapped['contact_person_last_name'] !== '' ? $mapped['contact_person_last_name'] : null,
                'imported_from' => 'telsearch',
                'import_fingerprint' => $fingerprint,
            ]);

            $this->upsertOffice($expert, $mapped, $canton);

            return 'created';
        });
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function upsertOffice(Expert $expert, array $mapped, ?Canton $canton): void
    {
        if (! $canton) {
            return;
        }

        $office = $expert->offices()->where('is_primary', true)->first()
            ?? $expert->offices()->first();

        $payload = [
            'label' => 'Hauptsitz',
            'address_line' => $mapped['address_line'] !== '' ? $mapped['address_line'] : '—',
            'postal_code' => $mapped['postal_code'] !== '' ? $mapped['postal_code'] : '0000',
            'city' => $mapped['city'] !== '' ? $mapped['city'] : ($canton->name_de ?? 'Schweiz'),
            'canton_id' => $canton->id,
            'latitude' => is_numeric($mapped['latitude']) ? (float) $mapped['latitude'] : $canton->latitude,
            'longitude' => is_numeric($mapped['longitude']) ? (float) $mapped['longitude'] : $canton->longitude,
            'is_primary' => true,
            'sort_order' => 0,
        ];

        if ($office) {
            $office->fill($payload)->save();
        } else {
            $expert->offices()->create($payload);
        }
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
        if ($url === '') {
            return '';
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
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
