<?php

namespace App\Http\Controllers\Verifikasi;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalReview;
use App\Models\User;
use App\Services\ProposalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Verifikasi Usulan dengan approval berjenjang (Bab 11, 17 & 22.3 kajian): Verifikator -> Tim
 * Standar Harga -> Pejabat Berwenang. Setiap role hanya melihat & memutuskan usulan yang sedang
 * berada di tahapnya sendiri; Super Admin melihat semua tahap sebagai fallback administratif.
 */
class ProposalReviewController extends Controller
{
    public function __construct(private readonly ProposalWorkflowService $workflowService)
    {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $statusAktif = $request->string('status')->toString() ?: 'menunggu_verifikasi';

        $proposals = Proposal::query()
            ->where('status', $statusAktif)
            ->when(
                $statusAktif === 'menunggu_verifikasi' && ! $user->isSuperAdmin(),
                fn ($q) => $q->where('tahapan_saat_ini', $this->tahapanUntuk($user))
            )
            ->with(['opd', 'items', 'creator'])
            ->oldest('diajukan_at')
            ->paginate(15)->withQueryString();

        return view('verifikasi.index', ['proposals' => $proposals, 'statusAktif' => $statusAktif]);
    }

    public function show(Proposal $proposal): View
    {
        return view('verifikasi.show', [
            'proposal' => $proposal->load(['items', 'reviews.reviewer', 'opd', 'creator']),
            'bisaMemutuskan' => $this->bisaMemutuskan(Auth::user(), $proposal),
        ]);
    }

    public function putuskan(Request $request, Proposal $proposal): RedirectResponse
    {
        abort_unless($this->bisaMemutuskan(Auth::user(), $proposal), 403, 'Usulan ini sedang menunggu tahap approval yang berbeda.');

        $validated = $request->validate([
            'keputusan' => ['required', 'in:setuju,revisi,tolak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $tahapanSebelum = $proposal->tahapan_saat_ini;
        $proposal = $this->workflowService->review($proposal, Auth::user(), $validated['keputusan'], $validated['catatan'] ?? null);

        $pesan = match (true) {
            $validated['keputusan'] === 'tolak' => "Usulan {$proposal->nomor_usulan} ditolak.",
            $validated['keputusan'] === 'revisi' => "Usulan {$proposal->nomor_usulan} dikembalikan untuk revisi.",
            $proposal->status->value === 'disetujui' => "Usulan {$proposal->nomor_usulan} disetujui pada tahap akhir & data master diperbarui.",
            default => "Usulan {$proposal->nomor_usulan} disetujui di tahap ".(ProposalReview::TAHAPAN[$tahapanSebelum] ?? $tahapanSebelum).", diteruskan ke tahap ".(ProposalReview::TAHAPAN[$proposal->tahapan_saat_ini] ?? $proposal->tahapan_saat_ini).".",
        };

        return redirect()->route('verifikasi.index')->with('status', $pesan);
    }

    private function tahapanUntuk(User $user): ?string
    {
        foreach (Proposal::TAHAPAN_URUTAN as $tahapan) {
            if ($user->hasRole(Proposal::roleForTahapan($tahapan))) {
                return $tahapan;
            }
        }

        return null;
    }

    private function bisaMemutuskan(User $user, Proposal $proposal): bool
    {
        if ($proposal->status->value !== 'menunggu_verifikasi') {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole(Proposal::roleForTahapan($proposal->tahapan_saat_ini));
    }
}
