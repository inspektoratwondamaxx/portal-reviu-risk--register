<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Asb;
use App\Models\AsbFormula;
use App\Models\AsbVariable;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Services\AsbCalculationService;
use App\Services\FormulaEvaluationException;
use App\Services\SafeFormulaEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** ASB: variabel kegiatan + formula parameterized -> hasil perhitungan (Bab 9 kajian). */
class AsbController extends Controller
{
    public function __construct(
        private readonly AsbCalculationService $calculationService,
        private readonly SafeFormulaEvaluator $evaluator,
    ) {
    }

    public function index(Request $request): View
    {
        $items = Asb::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nama_kegiatan', 'ilike', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->where('status', '!=', ItemStatus::Nonaktif->value))
            ->with('tahunAnggaran')
            ->orderBy('kode')
            ->paginate(20)->withQueryString();

        return view('admin.asb.index', ['items' => $items, 'statusOptions' => ItemStatus::options()]);
    }

    public function create(): View
    {
        return view('admin.asb.form', ['item' => new Asb, 'tahunList' => TahunAnggaran::orderByDesc('tahun')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:30', 'unique:asb,kode'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'kelompok_kegiatan' => ['nullable', 'string', 'max:255'],
            'satuan_variabel' => ['nullable', 'string', 'max:30'],
            'batas_minimal' => ['nullable', 'numeric', 'min:0'],
            'batas_maksimal' => ['nullable', 'numeric', 'min:0'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $item = Asb::create($validated + [
            'kode' => $validated['kode'] ?: 'ASB-'.now()->format('ymd').'-'.str_pad((string) ((Asb::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT),
            'status' => ItemStatus::Aktif->value,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.asb.show', $item)->with('status', 'ASB berhasil dibuat. Silakan tambahkan variabel & formula.');
    }

    public function show(Asb $asb): View
    {
        return view('admin.asb.show', [
            'item' => $asb->load(['variables', 'formula', 'tahunAnggaran']),
            'sshOptions' => SshItem::active()->orderBy('uraian')->get(),
            'hspkOptions' => Hspk::active()->orderBy('uraian')->get(),
            'sbuOptions' => SbuItem::active()->orderBy('uraian')->get(),
        ]);
    }

    public function edit(Asb $asb): View
    {
        return view('admin.asb.form', ['item' => $asb, 'tahunList' => TahunAnggaran::orderByDesc('tahun')->get()]);
    }

    public function update(Request $request, Asb $asb): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'kelompok_kegiatan' => ['nullable', 'string', 'max:255'],
            'satuan_variabel' => ['nullable', 'string', 'max:30'],
            'batas_minimal' => ['nullable', 'numeric', 'min:0'],
            'batas_maksimal' => ['nullable', 'numeric', 'min:0'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $asb->forceFill($validated + ['updated_by' => Auth::id()])->save();

        return redirect()->route('admin.asb.show', $asb)->with('status', 'ASB berhasil diperbarui.');
    }

    public function simpanVariabel(Request $request, Asb $asb): RedirectResponse
    {
        $validated = $request->validate([
            'kode_variabel' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'nilai' => ['required_if:sumber_tipe,manual', 'nullable', 'numeric'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'sumber_tipe' => ['required', 'in:manual,ssh_item,sbu_item,hspk'],
            'sumber_id' => ['nullable', 'integer'],
        ]);

        AsbVariable::updateOrCreate(
            ['asb_id' => $asb->id, 'kode_variabel' => $validated['kode_variabel']],
            [
                'label' => $validated['label'],
                'nilai' => $validated['nilai'] ?? 0,
                'satuan' => $validated['satuan'] ?? null,
                'sumber_tipe' => $validated['sumber_tipe'],
                'sumber_id' => $validated['sumber_tipe'] === 'manual' ? null : $validated['sumber_id'],
                'urutan' => $asb->variables()->count() + 1,
            ]
        );

        $this->calculationService->recalculate($asb->fresh(['variables', 'formula']));

        return back()->with('status', 'Variabel disimpan & ASB dihitung ulang.');
    }

    public function hapusVariabel(Asb $asb, AsbVariable $variable): RedirectResponse
    {
        abort_unless($variable->asb_id === $asb->id, 404);
        $variable->delete();
        $this->calculationService->recalculate($asb->fresh(['variables', 'formula']));

        return back()->with('status', 'Variabel dihapus & ASB dihitung ulang.');
    }

    public function simpanFormula(Request $request, Asb $asb): RedirectResponse
    {
        $validated = $request->validate(['ekspresi' => ['required', 'string', 'max:1000'], 'keterangan' => ['nullable', 'string']]);

        try {
            $variables = $this->evaluator->extractVariableNames($validated['ekspresi']);
            $tersedia = $asb->variables()->pluck('kode_variabel')->all();
            $hilang = array_diff($variables, $tersedia);
            if (! empty($hilang)) {
                return back()->withErrors(['ekspresi' => 'Variabel belum terdaftar: '.implode(', ', $hilang)])->withInput();
            }
        } catch (FormulaEvaluationException $e) {
            return back()->withErrors(['ekspresi' => $e->getMessage()])->withInput();
        }

        AsbFormula::updateOrCreate(
            ['asb_id' => $asb->id],
            ['ekspresi' => $validated['ekspresi'], 'keterangan' => $validated['keterangan'] ?? null, 'created_by' => Auth::id()]
        );

        $this->calculationService->recalculate($asb->fresh(['variables', 'formula']));

        return back()->with('status', 'Formula disimpan & ASB dihitung ulang.');
    }

    public function hitungUlang(Asb $asb): RedirectResponse
    {
        $this->calculationService->recalculate($asb->load(['variables', 'formula']));

        return back()->with('status', 'ASB berhasil dihitung ulang.');
    }

    public function nonaktifkan(Asb $asb): RedirectResponse
    {
        $asb->forceFill(['status' => ItemStatus::Nonaktif->value, 'is_active' => false])->save();

        return back()->with('status', "ASB \"{$asb->nama_kegiatan}\" dinonaktifkan.");
    }
}
