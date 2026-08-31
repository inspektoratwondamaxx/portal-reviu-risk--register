<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\Asb;
use App\Models\Hspk;
use App\Models\PriceHistory;
use App\Models\Proposal;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $tahunAktif = TahunAnggaran::aktif();

        $counts = [
            'ssh' => SshItem::active()->count(),
            'sbu' => SbuItem::active()->count(),
            'hspk' => Hspk::active()->count(),
            'asb' => Asb::active()->count(),
        ];

        $proposalQuery = Proposal::query();
        if ($user->hasRole(\App\Enums\RoleName::OpdOperator) && $user->opd_id) {
            $proposalQuery->where('opd_id', $user->opd_id);
        }

        $proposalStats = [
            'baru' => (clone $proposalQuery)->where('created_at', '>=', now()->subDays(30))->count(),
            'menunggu' => (clone $proposalQuery)->where('status', 'menunggu_verifikasi')->count(),
            'disetujui' => (clone $proposalQuery)->where('status', 'disetujui')->where('updated_at', '>=', now()->subDays(30))->count(),
            'ditolak' => (clone $proposalQuery)->where('status', 'ditolak')->where('updated_at', '>=', now()->subDays(30))->count(),
        ];

        $perTahun = TahunAnggaran::query()
            ->orderBy('tahun')
            ->get()
            ->map(fn (TahunAnggaran $t) => [
                'tahun' => $t->tahun,
                'ssh' => SshItem::where('tahun_anggaran_id', $t->id)->count(),
                'sbu' => SbuItem::where('tahun_anggaran_id', $t->id)->count(),
                'hspk' => Hspk::where('tahun_anggaran_id', $t->id)->count(),
                'asb' => Asb::where('tahun_anggaran_id', $t->id)->count(),
            ]);

        $totalSemua = array_sum($counts) ?: 1;
        $persentaseJenis = [
            'SSH' => round($counts['ssh'] / $totalSemua * 100, 1),
            'SBU' => round($counts['sbu'] / $totalSemua * 100, 1),
            'HSPK' => round($counts['hspk'] / $totalSemua * 100, 1),
            'ASB' => round($counts['asb'] / $totalSemua * 100, 1),
        ];

        $perubahanTerbaru = PriceHistory::with('user')->latest('tanggal')->latest('id')->take(8)->get();

        return view('dashboard.index', [
            'counts' => $counts,
            'proposalStats' => $proposalStats,
            'perTahun' => $perTahun,
            'persentaseJenis' => $persentaseJenis,
            'perubahanTerbaru' => $perubahanTerbaru,
            'tahunAktif' => $tahunAktif,
            'statusOptions' => ItemStatus::options(),
        ]);
    }
}
