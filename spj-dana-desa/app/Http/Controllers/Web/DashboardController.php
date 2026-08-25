<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Kampung;
use App\Models\PeriodeSpj;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PeriodeSpj::class);

        $user = $request->user();
        $kampungIds = $this->kampungIdsTerjangkau($user);

        $totalKampung = $kampungIds === null ? Kampung::count() : count($kampungIds);

        $statusSpj = PeriodeSpj::query()
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $totalRealisasi = Transaksi::query()
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->whereNotIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_REVISI])
            ->sum('nominal');

        $transaksiFlagged = Transaksi::query()
            ->with(['kampung', 'kegiatan', 'kodeRekening'])
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->where('is_flagged', true)
            ->orderByDesc('tanggal_transaksi')
            ->limit(10)
            ->get();

        $periodeMenungguPersetujuan = PeriodeSpj::query()
            ->with('kampung')
            ->when($kampungIds !== null, fn ($q) => $q->whereIn('kampung_id', $kampungIds))
            ->whereIn('status', [PeriodeSpj::STATUS_DIAJUKAN, PeriodeSpj::STATUS_DISETUJUI_PENDAMPING])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalKampung',
            'statusSpj',
            'totalRealisasi',
            'transaksiFlagged',
            'periodeMenungguPersetujuan',
        ));
    }

    public function kampung(Request $request, Kampung $kampung)
    {
        $this->authorize('viewAny', PeriodeSpj::class);

        $user = $request->user();
        $kampungIds = $this->kampungIdsTerjangkau($user);

        if ($kampungIds !== null && ! in_array($kampung->id, $kampungIds, true)) {
            abort(403, 'Kampung di luar wilayah binaan Anda.');
        }

        $realisasiPerKegiatan = $kampung->kegiatan()
            ->withSum(['transaksis as total_realisasi' => fn ($q) => $q->whereNotIn('status', [Transaksi::STATUS_DRAFT, Transaksi::STATUS_REVISI])], 'nominal')
            ->get();

        $periodeSpj = $kampung->periodeSpj()->orderByDesc('tahun_anggaran')->orderByDesc('bulan')->limit(12)->get();

        return view('dashboard.kampung', compact('kampung', 'realisasiPerKegiatan', 'periodeSpj'));
    }

    private function kampungIdsTerjangkau(User $user): ?array
    {
        if ($user->hasRole(User::ROLE_INSPEKTORAT, User::ROLE_ADMIN)) {
            return null;
        }

        if ($user->hasRole(User::ROLE_PENDAMPING)) {
            return $user->kampungBinaan()->pluck('kampungs.id')->all();
        }

        // kaur_keuangan/kepala_kampung: dibatasi kampung sendiri.
        return $user->kampung_id ? [$user->kampung_id] : [];
    }
}
