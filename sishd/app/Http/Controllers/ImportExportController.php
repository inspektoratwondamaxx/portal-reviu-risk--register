<?php

namespace App\Http\Controllers;

use App\Models\AssetCode;
use App\Models\Export;
use App\Models\Import;
use App\Models\TahunAnggaran;
use App\Services\ExportService;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Modul Import/Export (Bab 13 & 21 kajian): Excel sebagai media pertukaran, bukan penyimpanan utama. */
class ImportExportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly ExportService $exportService,
    ) {
    }

    public function index(): View
    {
        return view('import-export.index', [
            'imports' => Import::with('user')->latest()->take(10)->get(),
            'exports' => Export::with('user')->latest()->take(10)->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
            'assetCodes' => AssetCode::orderBy('kode')->get(),
        ]);
    }

    public function unduhTemplate(string $jenis): Response
    {
        abort_unless(in_array($jenis, ['ssh', 'sbu'], true), 404);
        $path = $this->importService->generateTemplate($jenis);

        return Storage::disk('local')->download($path, "template-{$jenis}.xlsx");
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:ssh,sbu'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $validated['file']->store('imports', 'local');

        $import = $validated['jenis'] === 'sbu'
            ? $this->importService->importSbu($path, Auth::user())
            : $this->importService->importSsh($path, Auth::user());

        return back()->with('status', "Import selesai: {$import->sukses} baris berhasil, {$import->gagal} gagal dari {$import->total_baris} baris.");
    }

    public function exportExcel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:ssh,sbu,hspk,asb'],
            'tahun_anggaran_id' => ['nullable', 'exists:tahun_anggarans,id'],
            'asset_code_id' => ['nullable', 'exists:asset_codes,id'],
        ]);

        $filters = array_filter($validated);
        $jenis = $filters['jenis'];
        unset($filters['jenis']);

        $export = match ($jenis) {
            'sbu' => $this->exportService->exportSbu($filters, Auth::user()),
            'hspk' => $this->exportService->exportHspk($filters, Auth::user()),
            'asb' => $this->exportService->exportAsb($filters, Auth::user()),
            default => $this->exportService->exportSsh($filters, Auth::user()),
        };

        return redirect()->route('import-export.download', $export)->with('status', "Export {$export->total_baris} baris berhasil dibuat.");
    }

    public function exportSipd(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:ssh,sbu'],
            'tahun_anggaran_id' => ['nullable', 'exists:tahun_anggarans,id'],
        ]);

        $jenis = $validated['jenis'];
        unset($validated['jenis']);

        $export = $this->exportService->exportSipd($jenis, array_filter($validated), Auth::user());

        return redirect()->route('import-export.download', $export)->with('status', 'Export format SIPD berhasil dibuat.');
    }

    public function download(Export $export): Response
    {
        return Storage::disk('local')->download($export->file_path, basename($export->file_path));
    }
}
