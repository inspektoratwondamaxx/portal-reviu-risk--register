<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Enums\KomponenType;
use App\Http\Controllers\Controller;
use App\Models\Hspk;
use App\Models\HspkComponent;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Services\HspkCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** HSPK: SSH/SBU (material/tenaga/peralatan) -> komponen -> harga satuan pekerjaan (Bab 8 kajian). */
class HspkController extends Controller
{
    public function __construct(private readonly HspkCalculationService $calculationService)
    {
    }

    public function index(Request $request): View
    {
        $items = Hspk::query()
            ->when($request->filled('q'), fn ($q) => $q->where('uraian', 'ilike', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->where('status', '!=', ItemStatus::Nonaktif->value))
            ->with('tahunAnggaran')
            ->orderBy('kode')
            ->paginate(20)->withQueryString();

        return view('admin.hspk.index', ['items' => $items, 'statusOptions' => ItemStatus::options()]);
    }

    public function create(): View
    {
        return view('admin.hspk.form', [
            'item' => new Hspk,
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:30', 'unique:hspk,kode'],
            'uraian' => ['required', 'string', 'max:255'],
            'jenis_pekerjaan' => ['nullable', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:30'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $item = Hspk::create($validated + [
            'kode' => $validated['kode'] ?: 'HSPK-'.now()->format('ymd').'-'.str_pad((string) ((Hspk::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT),
            'status' => ItemStatus::Aktif->value,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.hspk.show', $item)->with('status', 'HSPK berhasil dibuat. Silakan tambahkan komponen material/tenaga kerja/peralatan.');
    }

    public function show(Hspk $hspk): View
    {
        return view('admin.hspk.show', [
            'item' => $hspk->load(['components.sshItem', 'components.sbuItem', 'tahunAnggaran']),
            'riwayatAnalisis' => $hspk->analysis()->with('dihitungOleh')->take(10)->get(),
            'sshOptions' => SshItem::active()->orderBy('uraian')->get(),
            'sbuOptions' => SbuItem::active()->orderBy('uraian')->get(),
            'komponenTypes' => KomponenType::cases(),
        ]);
    }

    public function edit(Hspk $hspk): View
    {
        return view('admin.hspk.form', ['item' => $hspk, 'tahunList' => TahunAnggaran::orderByDesc('tahun')->get()]);
    }

    public function update(Request $request, Hspk $hspk): RedirectResponse
    {
        $validated = $request->validate([
            'uraian' => ['required', 'string', 'max:255'],
            'jenis_pekerjaan' => ['nullable', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:30'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $hspk->forceFill($validated + ['updated_by' => Auth::id()])->save();

        return redirect()->route('admin.hspk.show', $hspk)->with('status', 'HSPK berhasil diperbarui.');
    }

    public function tambahKomponen(Request $request, Hspk $hspk): RedirectResponse
    {
        $validated = $request->validate([
            'komponen_type' => ['required', 'in:material,tenaga_kerja,peralatan'],
            'ssh_item_id' => ['nullable', 'exists:ssh_items,id'],
            'sbu_item_id' => ['nullable', 'exists:sbu_items,id'],
            'uraian' => ['nullable', 'string', 'max:255'],
            'koefisien' => ['required', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
        ]);

        HspkComponent::create($validated + ['hspk_id' => $hspk->id, 'urutan' => $hspk->components()->count() + 1]);
        $this->calculationService->recalculate($hspk->fresh(), 'Tambah komponen manual');

        return back()->with('status', 'Komponen ditambahkan & HSPK dihitung ulang.');
    }

    public function hapusKomponen(Hspk $hspk, HspkComponent $component): RedirectResponse
    {
        abort_unless($component->hspk_id === $hspk->id, 404);
        $component->delete();
        $this->calculationService->recalculate($hspk->fresh(), 'Hapus komponen manual');

        return back()->with('status', 'Komponen dihapus & HSPK dihitung ulang.');
    }

    public function hitungUlang(Hspk $hspk): RedirectResponse
    {
        $this->calculationService->recalculate($hspk, 'Hitung ulang manual oleh '.(Auth::user()->name ?? 'admin'));

        return back()->with('status', 'HSPK berhasil dihitung ulang dari harga SSH/SBU terkini.');
    }

    public function nonaktifkan(Hspk $hspk): RedirectResponse
    {
        $hspk->forceFill(['status' => ItemStatus::Nonaktif->value, 'is_active' => false])->save();

        return back()->with('status', "HSPK \"{$hspk->uraian}\" dinonaktifkan.");
    }
}
