<?php

namespace App\Http\Controllers\SurveiHarga;

use App\Http\Controllers\Controller;
use App\Models\PriceEvidence;
use App\Models\PriceSurvey;
use App\Models\SshItem;
use App\Models\Vendor;
use App\Services\PriceSurveyAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Survei harga lapangan (Bab 15 & 22.4 kajian): dasar penetapan/perubahan harga SSH + bukti digital. */
class PriceSurveyController extends Controller
{
    public function __construct(private readonly PriceSurveyAnalysisService $analysisService)
    {
    }

    public function index(Request $request): View
    {
        $surveys = PriceSurvey::query()
            ->with(['sshItem', 'vendor', 'surveyor', 'evidence'])
            ->when($request->filled('q'), fn ($q) => $q->where('uraian_barang', 'ilike', '%'.$request->string('q').'%'))
            ->when($request->filled('ssh_item_id'), fn ($q) => $q->where('ssh_item_id', $request->integer('ssh_item_id')))
            ->latest('tanggal_survei')
            ->paginate(20)->withQueryString();

        $rekap = $request->filled('q') ? $this->analysisService->forUraian($request->string('q')->toString()) : null;

        return view('survei-harga.index', [
            'surveys' => $surveys,
            'vendors' => Vendor::where('is_active', true)->orderBy('nama')->get(),
            'sshItems' => SshItem::active()->orderBy('uraian')->get(),
            'rekap' => $rekap,
        ]);
    }

    public function create(): View
    {
        return view('survei-harga.form', [
            'vendors' => Vendor::where('is_active', true)->orderBy('nama')->get(),
            'sshItems' => SshItem::active()->orderBy('uraian')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ssh_item_id' => ['nullable', 'exists:ssh_items,id'],
            'uraian_barang' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'merek' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'tanggal_survei' => ['required', 'date'],
            'harga' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'bukti.*' => ['nullable', 'image', 'max:4096'],
            'jenis_bukti' => ['nullable', 'in:foto_toko,foto_daftar_harga,dokumen_penawaran,lainnya'],
        ]);

        $survey = PriceSurvey::create($validated + ['surveyor_id' => Auth::id()]);

        foreach ($request->file('bukti', []) as $file) {
            $path = $file->store('bukti-survei', 'public');
            PriceEvidence::create([
                'price_survey_id' => $survey->id,
                'file_path' => $path,
                'jenis_bukti' => $validated['jenis_bukti'] ?? 'lainnya',
            ]);
        }

        return redirect()->route('survei-harga.index')->with('status', 'Survei harga berhasil disimpan.');
    }

    public function show(PriceSurvey $priceSurvey): View
    {
        return view('survei-harga.show', [
            'survey' => $priceSurvey->load(['sshItem', 'vendor', 'surveyor', 'evidence']),
            'rekap' => $priceSurvey->ssh_item_id
                ? $this->analysisService->forSshItem($priceSurvey->ssh_item_id)
                : $this->analysisService->forUraian($priceSurvey->uraian_barang),
        ]);
    }

    public function destroy(PriceSurvey $priceSurvey): RedirectResponse
    {
        foreach ($priceSurvey->evidence as $evidence) {
            Storage::disk('public')->delete($evidence->file_path);
        }
        $priceSurvey->delete();

        return back()->with('status', 'Data survei dihapus.');
    }
}
