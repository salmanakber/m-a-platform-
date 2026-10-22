<?php

namespace App\Console\Commands;

use App\Models\ExpertOffice;
use App\Services\Maps\GeocodingService;
use Illuminate\Console\Command;
use Throwable;

class GeocodeExpertOfficesCommand extends Command
{
    protected $signature = 'experts:geocode-offices
                            {--only-missing : Only offices without coords or parked on canton center}
                            {--limit=0 : Max offices to process (0 = all)}';

    protected $description = 'Geocode expert office addresses to street-level map coordinates';

    public function handle(GeocodingService $geocoder): int
    {
        $onlyMissing = (bool) $this->option('only-missing');
        $limit = (int) $this->option('limit');

        $query = ExpertOffice::query()->with(['canton', 'expert'])->orderBy('id');
        $offices = $query->get();

        if ($onlyMissing) {
            $offices = $offices->filter(function (ExpertOffice $office) {
                if ($office->latitude === null || $office->longitude === null) {
                    return true;
                }
                if (! $office->canton || $office->canton->latitude === null || $office->canton->longitude === null) {
                    return $office->latitude === null || $office->longitude === null;
                }

                return abs((float) $office->latitude - (float) $office->canton->latitude) < 0.0001
                    && abs((float) $office->longitude - (float) $office->canton->longitude) < 0.0001;
            })->values();
        }

        if ($limit > 0) {
            $offices = $offices->take($limit);
        }

        $this->info('Geocoding '.$offices->count().' office(s)…');
        $ok = 0;
        $fail = 0;

        foreach ($offices as $office) {
            $label = ($office->expert->company_name ?? 'Office #'.$office->id).' — '.$office->address_line.', '.$office->postal_code.' '.$office->city;
            try {
                $coords = $geocoder->geocodeSwissAddress(
                    $office->address_line !== '—' ? $office->address_line : null,
                    $office->postal_code !== '0000' ? $office->postal_code : null,
                    $office->city,
                    $office->canton->code ?? null
                );
                if ($coords === null) {
                    $this->warn('  miss: '.$label);
                    $fail++;
                    continue;
                }
                $office->update([
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                ]);
                $ok++;
                $this->line('  ok: '.$label.' → '.$coords['latitude'].', '.$coords['longitude']);
                usleep(200000);
            } catch (Throwable $e) {
                $fail++;
                $this->error('  err: '.$label.' — '.$e->getMessage());
            }
        }

        $this->info("Done. Geocoded: {$ok}, failed: {$fail}");

        return self::SUCCESS;
    }
}
