<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Asb;
use App\Models\Hspk;
use App\Models\PriceHistory;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Laporan (Bab 18 kajian): rekap per jenis, perubahan harga, dan riwayat data. */
class LaporanController extends Controller
{
    public function rekap(Request $request, string $jenis): View
    {
        abort_unless(in_array($jenis, ['ssh', 'sbu', 'hspk', 'asb'], true), 404);

        $tahunId = $request->filled('tahun') ? TahunAnggaran::where('tahun', $request->integer('tahun'))->value('id') : null;

        $query = match ($jenis) {
            'sbu' => SbuItem::query()->selectRaw('kategori as kelompok, count(*) as jumlah, sum(besaran) as total')->groupBy('kategori'),
            'hspk' => Hspk::query()->selectRaw('jenis_pekerjaan as kelompok, count(*) as jumlah, sum(harga_satuan) as total')->groupBy('jenis_pekerjaan'),
            'asb' => Asb::query()->selectRaw('kelompok_kegiatan as kelompok, count(*) as jumlah, sum(hasil_perhitungan) as total')->groupBy('kelompok_kegiatan'),
            default => SshItem::query()->selectRaw('category_id, count(*) as jumlah, sum(harga) as total')->groupBy('category_id')->with('category'),
        };

        if ($tahunId) {
            $query->where('tahun_anggaran_id', $tahunId);
        }

        return view('laporan.rekap', [
            'jenis' => $jenis,
            'rows' => $query->get(),
            'tahunList' => TahunAnggaran::orderByDesc('tahun')->get(),
        ]);
    }

    public function perubahanHarga(Request $request): View
    {
        $histories = PriceHistory::query()
            ->with('user')
            ->when($request->filled('jenis'), fn ($q) => $q->where('item_type', $request->string('jenis')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->date('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->date('sampai')))
            ->latest('tanggal')
            ->paginate(25)->withQueryString();

        return view('laporan.perubahan-harga', ['histories' => $histories]);
    }

    public function riwayatData(Request $request): View
    {
        $logs = \App\Models\AuditLog::query()
            ->with('user')
            ->when($request->filled('model'), fn ($q) => $q->where('model_type', 'like', '%'.$request->string('model').'%'))
            ->when($request->filled('aksi'), fn ($q) => $q->where('action', $request->string('aksi')))
            ->latest()
            ->paginate(30)->withQueryString();

        return view('laporan.riwayat-data', ['logs' => $logs]);
    }
}
