<?php

namespace App\Http\Controllers\Opd;

use App\Http\Controllers\Controller;
use App\Models\AssetCode;
use App\Models\Proposal;
use App\Models\TahunAnggaran;
use App\Services\DuplicateDetectionService;
use App\Services\ProposalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Usulan OPD (Bab 11-12 kajian): OPD tidak boleh mengubah master langsung, semua lewat usulan. */
class ProposalController extends Controller
{
    public function __construct(
        private readonly ProposalWorkflowService $workflowService,
        private readonly DuplicateDetectionService $duplicateDetectionService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $proposals = Proposal::query()
            ->when(! $user->isSuperAdmin() && ! $user->hasRole(\App\Enums\RoleName::Verifikator), fn ($q) => $q->where('opd_id', $user->opd_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis_usulan', $request->string('jenis')))
            ->with(['opd', 'items', 'creator'])
            ->latest('diajukan_at')
            ->paginate(15)->withQueryString();

        return view('opd.usulan.index', ['proposals' => $proposals]);
    }

    public function create(Request $request): View
    {
        return view('opd.usulan.create', [
            'jenis' => $request->string('jenis')->toString() ?: 'ssh',
            'tahunAktif' => TahunAnggaran::aktif(),
            'assetCodes' => AssetCode::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_usulan' => ['required', 'in:ssh,sbu,hspk,asb'],
            'tipe_perubahan' => ['required', 'in:baru,perubahan,nonaktif'],
            'alasan_usulan' => ['required', 'string'],
            'uraian' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'merek' => ['nullable', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:30'],
            'harga' => ['required', 'numeric', 'min:0'],
            'sumber_harga' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'existing_item_id' => ['nullable', 'integer'],
        ]);

        $user = Auth::user();
        $tahunAktif = TahunAnggaran::aktif();

        $dataUsulan = [
            'uraian' => $validated['uraian'],
            'spesifikasi' => $validated['spesifikasi'] ?? null,
            'merek' => $validated['merek'] ?? null,
            'satuan' => $validated['satuan'],
            'harga' => $validated['harga'],
            'sumber_harga' => $validated['sumber_harga'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'tahun_anggaran_id' => $tahunAktif?->id,
        ];

        $proposal = $this->workflowService->createProposal(
            $user->opd_id,
            $validated['jenis_usulan'],
            $validated['tipe_perubahan'],
            $validated['alasan_usulan'],
            [['existing_item_id' => $validated['existing_item_id'] ?? null, 'data_usulan' => $dataUsulan]],
            $user,
        );

        return redirect()->route('opd.usulan.show', $proposal)->with('status', "Usulan {$proposal->nomor_usulan} berhasil diajukan.");
    }

    public function cekSerupa(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['uraian' => ['required', 'string'], 'merek' => ['nullable', 'string']]);

        return response()->json(['hasil' => $this->duplicateDetectionService->findSimilar($validated['uraian'], $validated['merek'] ?? null)->values()]);
    }

    public function show(Proposal $proposal): View
    {
        $this->authorizeAccess($proposal);

        return view('opd.usulan.show', ['proposal' => $proposal->load(['items', 'reviews.reviewer', 'opd', 'creator', 'verifikator'])]);
    }

    public function ajukanUlang(Proposal $proposal): RedirectResponse
    {
        $this->authorizeAccess($proposal);
        abort_unless($proposal->status->value === 'revisi', 403);

        $this->workflowService->resubmit($proposal);

        return back()->with('status', 'Usulan diajukan ulang untuk diverifikasi.');
    }

    private function authorizeAccess(Proposal $proposal): void
    {
        $user = Auth::user();
        abort_unless($user->isSuperAdmin() || $user->hasRole(\App\Enums\RoleName::Verifikator) || $proposal->opd_id === $user->opd_id, 403);
    }
}
