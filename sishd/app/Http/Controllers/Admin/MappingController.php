<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCode;
use App\Models\AssetCode;
use App\Models\CodeMapping;
use App\Models\SipdCode;
use App\Services\CodeMappingValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Mapping Kode Aset -> Kode Rekening -> Kode SIPD, inti persoalan pemetaan lintas-OPD (Bab 10 kajian). */
class MappingController extends Controller
{
    public function __construct(private readonly CodeMappingValidationService $validationService)
    {
    }

    public function index(Request $request): View
    {
        $mappings = CodeMapping::query()
            ->with(['assetCode', 'accountCode', 'sipdCode'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('assetCode', fn ($a) => $a->where('kode', 'ilike', '%'.$request->string('q').'%')->orWhere('nama', 'ilike', '%'.$request->string('q').'%')))
            ->orderBy('id')
            ->paginate(20)->withQueryString();

        $belumDipetakan = AssetCode::query()->where('is_active', true)->whereDoesntHave('codeMappings')->paginate(10, ['*'], 'belum_page');

        return view('admin.mapping.index', [
            'mappings' => $mappings,
            'belumDipetakan' => $belumDipetakan,
            'assetCodes' => AssetCode::orderBy('kode')->get(),
            'accountCodes' => AccountCode::orderBy('kode')->get(),
            'sipdCodes' => SipdCode::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_code_id' => ['required', 'exists:asset_codes,id'],
            'account_code_id' => ['nullable', 'exists:account_codes,id'],
            'sipd_code_id' => ['nullable', 'exists:sipd_codes,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $mapping = CodeMapping::create($validated + ['status' => 'tidak_ditemukan']);
        $this->validationService->validateAll();

        return redirect()->route('admin.mapping.index')->with('status', 'Mapping kode berhasil disimpan.');
    }

    public function update(Request $request, CodeMapping $mapping): RedirectResponse
    {
        $validated = $request->validate([
            'account_code_id' => ['nullable', 'exists:account_codes,id'],
            'sipd_code_id' => ['nullable', 'exists:sipd_codes,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $mapping->update($validated);
        $this->validationService->validateAll();

        return back()->with('status', 'Mapping kode berhasil diperbarui.');
    }

    public function destroy(CodeMapping $mapping): RedirectResponse
    {
        $mapping->delete();
        $this->validationService->validateAll();

        return back()->with('status', 'Mapping kode dihapus.');
    }

    public function validasi(): RedirectResponse
    {
        $summary = $this->validationService->validateAll();

        return back()->with('status', sprintf(
            'Validasi selesai: %d valid, %d belum ada rekening, %d duplikasi, %d kode aset belum dipetakan.',
            $summary['valid'], $summary['belum_rekening'], $summary['duplikasi'], $summary['tidak_ditemukan']
        ));
    }

    public function cekKode(Request $request)
    {
        $validated = $request->validate(['kode' => ['required', 'string']]);
        $hasil = $this->validationService->checkKode($validated['kode']);

        return response()->json([
            'status' => $hasil['status']->value,
            'label' => $hasil['status']->label(),
            'icon' => $hasil['status']->icon(),
            'asset_code' => $hasil['asset_code'],
            'mappings' => $hasil['mappings'],
        ]);
    }
}
