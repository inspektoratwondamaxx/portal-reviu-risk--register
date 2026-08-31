<?php

namespace App\Http\Controllers\Verifikasi;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\ProposalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Verifikasi Usulan (Bab 11 & 17 kajian): role verifikator memeriksa & memutuskan usulan OPD. */
class ProposalReviewController extends Controller
{
    public function __construct(private readonly ProposalWorkflowService $workflowService)
    {
    }

    public function index(Request $request): View
    {
        $proposals = Proposal::query()
            ->where('status', $request->string('status')->toString() ?: 'menunggu_verifikasi')
            ->with(['opd', 'items', 'creator'])
            ->oldest('diajukan_at')
            ->paginate(15)->withQueryString();

        return view('verifikasi.index', ['proposals' => $proposals, 'statusAktif' => $request->string('status')->toString() ?: 'menunggu_verifikasi']);
    }

    public function show(Proposal $proposal): View
    {
        return view('verifikasi.show', ['proposal' => $proposal->load(['items', 'reviews.reviewer', 'opd', 'creator'])]);
    }

    public function putuskan(Request $request, Proposal $proposal): RedirectResponse
    {
        $validated = $request->validate([
            'keputusan' => ['required', 'in:setuju,revisi,tolak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $this->workflowService->review($proposal, Auth::user(), $validated['keputusan'], $validated['catatan'] ?? null);

        $pesan = match ($validated['keputusan']) {
            'setuju' => "Usulan {$proposal->nomor_usulan} disetujui & data master diperbarui.",
            'revisi' => "Usulan {$proposal->nomor_usulan} dikembalikan untuk revisi.",
            'tolak' => "Usulan {$proposal->nomor_usulan} ditolak.",
        };

        return redirect()->route('verifikasi.index')->with('status', $pesan);
    }
}
