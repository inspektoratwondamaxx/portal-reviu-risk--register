<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Asb;
use App\Models\Category;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Website publik (Bab 3 & 18 kajian): pencarian data standar harga yang telah aktif/dipublikasikan, tanpa login. */
class BerandaController extends Controller
{
    public function index(): View
    {
        return view('publik.beranda', [
            'ringkasan' => [
                'ssh' => SshItem::active()->count(),
                'sbu' => SbuItem::active()->count(),
                'hspk' => Hspk::active()->count(),
                'asb' => Asb::active()->count(),
            ],
            'kategoriPopuler' => Category::withCount(['sshItems' => fn ($q) => $q->active()])
                ->whereNull('parent_id')->orderByDesc('ssh_items_count')->take(4)->get(),
        ]);
    }

    public function cari(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'in:ssh,sbu,hspk,asb'],
            'kategori_id' => ['nullable', 'integer'],
            'tahun' => ['nullable', 'integer'],
            'kode' => ['nullable', 'string', 'max:30'],
        ]);

        $jenis = $validated['jenis'] ?? 'ssh';
        $tahunId = ! empty($validated['tahun']) ? TahunAnggaran::where('tahun', $validated['tahun'])->value('id') : null;

        $results = match ($jenis) {
            'sbu' => SbuItem::active()
                ->when($validated['q'] ?? null, fn ($q, $term) => $q->where('uraian', 'ilike', "%{$term}%"))
                ->when($tahunId, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
                ->when($validated['kode'] ?? null, fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
                ->orderBy('uraian')->paginate(15)->withQueryString(),
            'hspk' => Hspk::active()
                ->when($validated['q'] ?? null, fn ($q, $term) => $q->where('uraian', 'ilike', "%{$term}%"))
                ->when($tahunId, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
                ->when($validated['kode'] ?? null, fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
                ->orderBy('uraian')->paginate(15)->withQueryString(),
            'asb' => Asb::active()
                ->when($validated['q'] ?? null, fn ($q, $term) => $q->where('nama_kegiatan', 'ilike', "%{$term}%"))
                ->when($tahunId, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
                ->when($validated['kode'] ?? null, fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
                ->orderBy('nama_kegiatan')->paginate(15)->withQueryString(),
            default => SshItem::active()->with(['category', 'assetCode'])
                ->when($validated['q'] ?? null, fn ($q, $term) => $q->search($term))
                ->when($validated['kategori_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
                ->when($tahunId, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
                ->when($validated['kode'] ?? null, fn ($q, $kode) => $q->where('kode_barang', 'ilike', "%{$kode}%"))
                ->orderBy('uraian')->paginate(15)->withQueryString(),
        };

        return view('publik.hasil-pencarian', [
            'results' => $results,
            'jenis' => $jenis,
            'filters' => $validated,
            'kategoriList' => Category::whereNull('parent_id')->orderBy('nama')->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function detailSsh(SshItem $ssh): View
    {
        abort_unless($ssh->is_active, 404);

        return view('publik.detail-ssh', [
            'item' => $ssh->load(['category', 'assetCode', 'opd']),
            'riwayat' => $ssh->priceHistories()->latest('tanggal')->get(),
        ]);
    }

    public function detailHspk(Hspk $hspk): View
    {
        abort_unless($hspk->is_active, 404);

        return view('publik.detail-hspk', [
            'item' => $hspk->load(['components.sshItem', 'components.sbuItem', 'tahunAnggaran']),
        ]);
    }
}
