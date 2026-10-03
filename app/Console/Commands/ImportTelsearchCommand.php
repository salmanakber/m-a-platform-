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
                            {--file= : Absolute path to an xlsx/xls/csv file}';

    protected $description = 'Import experts from Telsearch / spreadsheet file';

    public function handle(TelsearchImportService $imports): int
    {
        try {
            if ($absolute = $this->option('file')) {
                if (! is_file($absolute)) {
                    $this->error('File not found: '.$absolute);

                    return self::FAILURE;
                }
                $ext = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'xlsx';
                $storagePath = 'imports/cli_'.now()->format('Ymd_His').'.'.$ext;
                Storage::disk('local')->put($storagePath, file_get_contents($absolute));
                $result = $imports->importPath($storagePath, basename($absolute));
            } else {
                $path = $this->option('path') ?: TelsearchImportService::IMPORT_PATH;
                $result = $imports->importPath($path, basename($path));
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Import OK — created: %d, updated: %d, skipped: %d, failed: %d (run #%d)',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['failed'],
            $result['run']->id
        ));

        return self::SUCCESS;
    }
}
