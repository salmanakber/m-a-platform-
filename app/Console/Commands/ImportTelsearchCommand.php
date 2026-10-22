<?php

namespace App\Console\Commands;

use App\Services\Import\TelsearchImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportTelsearchCommand extends Command
{
    protected $signature = 'import:telsearch
                            {--path= : Relative path under storage/app (default: imports/Basis_telsearch.xlsx)}
                            {--file= : Absolute path to an xlsx/xls/csv file}
                            {--geocode=1 : Geocode addresses to lat/lng (1/0)}';

    protected $description = 'Import experts from Telsearch / spreadsheet file (including office locations)';

    public function handle(TelsearchImportService $imports): int
    {
        $geocode = ! in_array((string) $this->option('geocode'), ['0', 'false', 'no'], true);

        try {
            if ($absolute = $this->option('file')) {
                if (! is_file($absolute)) {
                    $this->error('File not found: '.$absolute);

                    return self::FAILURE;
                }
                $ext = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'xlsx';
                $storagePath = 'imports/cli_'.now()->format('Ymd_His').'.'.$ext;
                Storage::disk('local')->put($storagePath, file_get_contents($absolute));
                $result = $imports->importPath($storagePath, basename($absolute), null, $geocode);
            } else {
                $path = $this->option('path') ?: TelsearchImportService::IMPORT_PATH;
                $result = $imports->importPath($path, basename($path), null, $geocode);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Import OK — created: %d, updated: %d, skipped: %d, failed: %d, geocoded: %d (run #%d)',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['failed'],
            $result['geocoded'] ?? 0,
            $result['run']->id
        ));

        return self::SUCCESS;
    }
}
