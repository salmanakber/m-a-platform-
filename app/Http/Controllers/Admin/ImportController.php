<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportRun;
use App\Services\Import\TelsearchImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ImportController extends Controller
{
    public function __construct(private TelsearchImportService $imports)
    {
    }

    public function index(): View
    {
        $this->imports->ensureTemplate();

        $defaultExists = Storage::disk('local')->exists(TelsearchImportService::IMPORT_PATH);
        $runs = ImportRun::query()
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        $pendingImported = \App\Models\Expert::query()
            ->where('imported_from', 'telsearch')
            ->where('status', \App\Support\ExpertStatus::PENDING)
            ->count();

        return view('admin.import.index', [
            'defaultExists' => $defaultExists,
            'defaultPath' => 'storage/app/'.TelsearchImportService::IMPORT_PATH,
            'runs' => $runs,
            'pendingImported' => $pendingImported,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,txt', 'max:51200'],
            'use_default' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $useDefault = (bool) ($validated['use_default'] ?? false);

        if (! $file && ! $useDefault) {
            return back()->withErrors([
                'file' => 'Bitte eine Datei hochladen oder die Ablage-Datei verwenden.',
            ]);
        }

        if ($useDefault && ! Storage::disk('local')->exists(TelsearchImportService::IMPORT_PATH)) {
            return back()->withErrors([
                'use_default' => 'Keine Datei unter storage/app/imports/Basis_telsearch.xlsx gefunden.',
            ]);
        }

        try {
            $result = $this->imports->importFromUpload(
                $useDefault ? null : $file,
                $request->user()?->id
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'file' => 'Import fehlgeschlagen: '.$e->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            sprintf(
                'Import abgeschlossen: %d neu, %d aktualisiert, %d übersprungen, %d Fehler.',
                $result['created'],
                $result['updated'],
                $result['skipped'],
                $result['failed']
            )
        );
    }

    public function template(): BinaryFileResponse
    {
        $path = $this->imports->ensureTemplate();

        return response()->download($path, 'nachfolge-experten-import-vorlage.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
