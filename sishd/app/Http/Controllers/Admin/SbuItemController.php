<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\SbuItem;
use App\Models\TahunAnggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SbuItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = SbuItem::query()
            ->when($request->filled('q'), fn ($q) => $q->where('uraian', 'ilike', '%'.$request->string('q').'%'))
            ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->string('kategori')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->where('status', '!=', ItemStatus::Nonaktif->value))
            ->with('tahunAnggaran')
            ->orderBy('kategori')->orderBy('uraian')
            ->paginate(20)->withQueryString();

        return view('admin.sbu.index', [
            'items' => $items,
            'kategoriOptions' => SbuItem::KATEGORI,
            'statusOptions' => ItemStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sbu.form', [
            'item' => new SbuItem,
            'kategoriOptions' => SbuItem::KATEGORI,
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $item = SbuItem::create($validated + [
            'kode' => $validated['kode'] ?: 'SBU-'.now()->format('ymd').'-'.str_pad((string) ((SbuItem::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT),
            'status' => ItemStatus::Aktif->value,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.sbu.index')->with('status', "SBU \"{$item->uraian}\" berhasil disimpan.");
    }

    public function edit(SbuItem $sbu): View
    {
        return view('admin.sbu.form', [
            'item' => $sbu,
            'kategoriOptions' => SbuItem::KATEGORI,
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function update(Request $request, SbuItem $sbu): RedirectResponse
    {
        $validated = $this->validated($request, $sbu->id);

        $sbu->pendingDasarPerubahan = $request->string('dasar_perubahan')->toString() ?: 'Perbarui Master SBU';
        $sbu->fill($validated)->update();
        $sbu->forceFill(['updated_by' => Auth::id()])->saveQuietly();

        return redirect()->route('admin.sbu.index')->with('status', "SBU \"{$sbu->uraian}\" berhasil diperbarui.");
    }

    public function nonaktifkan(SbuItem $sbu): RedirectResponse
    {
        $sbu->forceFill(['status' => ItemStatus::Nonaktif->value, 'is_active' => false])->save();

        return back()->with('status', "SBU \"{$sbu->uraian}\" dinonaktifkan.");
    }

    public function aktifkan(SbuItem $sbu): RedirectResponse
    {
        $sbu->forceFill(['status' => ItemStatus::Aktif->value, 'is_active' => true])->save();

        return back()->with('status', "SBU \"{$sbu->uraian}\" diaktifkan kembali.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode' => ['nullable', 'string', 'max:30', 'unique:sbu_items,kode'.($ignoreId ? ",{$ignoreId}" : '')],
            'kategori' => ['required', 'in:'.implode(',', array_keys(SbuItem::KATEGORI))],
            'uraian' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:30'],
            'wilayah' => ['nullable', 'string', 'max:255'],
            'besaran' => ['required', 'numeric', 'min:0'],
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'dasar_penetapan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
