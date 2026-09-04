<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\AssetCode;
use App\Models\Category;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Services\DuplicateDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SshItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = SshItem::query()
            ->with(['assetCode', 'category', 'tahunAnggaran'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('kategori'), fn ($q) => $q->where('category_id', $request->integer('kategori')))
            ->when($request->filled('tahun'), fn ($q) => $q->whereHas('tahunAnggaran', fn ($t) => $t->where('tahun', $request->integer('tahun'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->where('status', '!=', ItemStatus::Nonaktif->value))
            ->orderBy('uraian')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ssh.index', [
            'items' => $items,
            'kategoriList' => Category::orderBy('nama')->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
            'statusOptions' => ItemStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ssh.form', [
            'item' => new SshItem,
            'assetCodes' => AssetCode::orderBy('kode')->get(),
            'kategoriList' => Category::orderBy('nama')->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function cekSerupa(Request $request, DuplicateDetectionService $service)
    {
        $validated = $request->validate(['uraian' => ['required', 'string'], 'merek' => ['nullable', 'string']]);

        return response()->json([
            'hasil' => $service->findSimilar($validated['uraian'], $validated['merek'] ?? null)->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $item = SshItem::create($validated + [
            'kode_barang' => $validated['kode_barang'] ?: $this->generateKode(),
            'status' => ItemStatus::Aktif->value,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.ssh.index')->with('status', "SSH \"{$item->uraian}\" berhasil disimpan.");
    }

    public function edit(SshItem $ssh): View
    {
        return view('admin.ssh.form', [
            'item' => $ssh,
            'assetCodes' => AssetCode::orderBy('kode')->get(),
            'kategoriList' => Category::orderBy('nama')->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function update(Request $request, SshItem $ssh): RedirectResponse
    {
        $validated = $this->validated($request, $ssh->id);

        $ssh->pendingDasarPerubahan = $request->string('dasar_perubahan')->toString() ?: 'Perbarui Master SSH';
        $ssh->fill($validated)->update();
        $ssh->forceFill(['updated_by' => Auth::id()])->saveQuietly();

        return redirect()->route('admin.ssh.index')->with('status', "SSH \"{$ssh->uraian}\" berhasil diperbarui.");
    }

    public function show(SshItem $ssh): View
    {
        return view('admin.ssh.show', [
            'item' => $ssh->load(['assetCode', 'accountCode', 'category', 'tahunAnggaran', 'opd']),
            'riwayat' => $ssh->priceHistories()->with('user')->latest('tanggal')->get(),
            'hspkTerkait' => $ssh->hspkComponents()->with('hspk')->get()->pluck('hspk')->unique('id'),
        ]);
    }

    public function nonaktifkan(SshItem $ssh): RedirectResponse
    {
        $ssh->forceFill(['status' => ItemStatus::Nonaktif->value, 'is_active' => false, 'updated_by' => Auth::id()])->save();

        return back()->with('status', "SSH \"{$ssh->uraian}\" dinonaktifkan (data tetap tersimpan untuk histori).");
    }

    public function aktifkan(SshItem $ssh): RedirectResponse
    {
        $ssh->forceFill(['status' => ItemStatus::Aktif->value, 'is_active' => true, 'updated_by' => Auth::id()])->save();

        return back()->with('status', "SSH \"{$ssh->uraian}\" diaktifkan kembali.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode_barang' => ['nullable', 'string', 'max:30', 'unique:ssh_items,kode_barang'.($ignoreId ? ",{$ignoreId}" : '')],
            'asset_code_id' => ['nullable', 'exists:asset_codes,id'],
            'account_code_id' => ['nullable', 'exists:account_codes,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'uraian' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'merek' => ['nullable', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:30'],
            'harga' => ['required', 'numeric', 'min:0'],
            'sumber_harga' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    private function generateKode(): string
    {
        $next = (SshItem::max('id') ?? 0) + 1;

        return 'SSH-'.now()->format('ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
