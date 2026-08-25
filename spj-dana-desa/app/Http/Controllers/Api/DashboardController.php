<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kampung;
use App\Models\PeriodeSpj;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;

/** Bab VI.6 kajian teknis — dashboard pendamping/inspektorat, KF-13. */
class DashboardController extends Controller
{
    public function ringkasan(Request $request)
    {
        $this->authorize('viewAny', PeriodeSpj::class);

        $kampungIds = $this->kampungIdsTerjangkau($request->user());

        $totalKampung = $kampungIds === null ? Kampung::count() : count($kampungIds);

        $statusSpj = PeriodeSpj::query()
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->when($request->query('tahun'), fn ($q, $tahun) => $q->where('tahun_anggaran', $tahun))
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $totalRealisasi = Transaksi::query()
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->whereNotIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_REVISI])
            ->when($request->query('tahun'), fn ($q, $tahun) => $q->whereYear('tanggal_transaksi', $tahun))
            ->sum('nominal');

        $jumlahFlagged = Transaksi::query()
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->where('is_flagged', true)
            ->count();

        return $this->ok([
            'total_kampung' => $totalKampung,
            'status_spj' => $statusSpj,
            'total_realisasi_belanja' => $totalRealisasi,
            'jumlah_transaksi_flagged' => $jumlahFlagged,
        ]);
    }

    public function kampung(Request $request, Kampung $kampung)
    {
        $this->authorize('viewAny', PeriodeSpj::class);

        $kampungIds = $this->kampungIdsTerjangkau($request->user());

        if ($kampungIds !== null && ! in_array($kampung->id, $kampungIds, true)) {
            abort(403, 'Kampung di luar wilayah binaan Anda.');
        }

        $realisasiPerKegiatan = $kampung->kegiatan()
            ->withSum(['transaksis as total_realisasi' => fn ($q) => $q->whereNotIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_REVISI])], 'nominal')
            ->get(['id', 'nama_kegiatan', 'pagu_total']);

        return $this->ok([
            'kampung' => $kampung,
            'realisasi_per_kegiatan' => $realisasiPerKegiatan,
            'periode_spj' => $kampung->periodeSpj()->orderByDesc('tahun_anggaran')->orderByDesc('bulan')->limit(12)->get(),
        ]);
    }

    public function transaksiFlagged(Request $request)
    {
        $this->authorize('viewAny', Transaksi::class);

        $kampungIds = $this->kampungIdsTerjangkau($request->user());

        $transaksis = Transaksi::query()
            ->with(['kampung', 'kegiatan', 'kodeRekening'])
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->where('is_flagged', true)
            ->orderByDesc('tanggal_transaksi')
            ->paginate(20);

        return $this->ok($transaksis->items(), ['total' => $transaksis->total()]);
    }

    /** null berarti akses lintas kampung penuh (inspektorat/admin). */
    private function kampungIdsTerjangkau(User $user): ?array
    {
        if ($user->hasRole(User::ROLE_INSPEKTORAT, User::ROLE_ADMIN)) {
            return null;
        }

        if ($user->hasRole(User::ROLE_PENDAMPING)) {
            return $user->kampungBinaan()->pluck('kampungs.id')->all();
        }

        // Endpoint ini digerbangi middleware role:pendamping,inspektorat,admin — cabang ini
        // hanya jaring pengaman bila helper dipanggil ulang dari konteks lain di masa depan.
        return $user->kampung_id ? [$user->kampung_id] : [];
    }
}
