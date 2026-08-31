<?php

namespace App\Http\Controllers\Opd;

use App\Models\Asb;
use App\Models\SbuItem;
use App\Http\Controllers\Controller;
use App\Models\Hspk;
use App\Models\Proposal;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Services\DuplicateDetectionService;
use App\Services\ProposalWorkflowService;
use App\Support\ItemTypeResolver;
use Illuminate\Http\JsonResponse;
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
            'kategoriSbu' => SbuItem::KATEGORI,
        ]);
    }

    /**
     * Field yang wajib/dipakai berbeda per jenis_usulan — SSH/SBU/HSPK pakai "uraian", ASB pakai
     * "nama_kegiatan" (kolomnya NOT NULL di tabel asb, beda dari "uraian" yang dikirim form generik
     * versi sebelumnya). SBU perlu "kategori" & "besaran", bukan "harga" mentah.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->input('tipe_perubahan') === 'nonaktif') {
            return $this->storeNonaktif($request);
        }

        $validated = $request->validate([
            'jenis_usulan' => ['required', 'in:ssh,sbu,hspk,asb'],
            'tipe_perubahan' => ['required', 'in:baru,perubahan,nonaktif'],
            'alasan_usulan' => ['required', 'string'],
            'existing_item_id' => ['nullable', 'integer', 'required_if:tipe_perubahan,perubahan'],

            'uraian' => ['required_unless:jenis_usulan,asb', 'nullable', 'string', 'max:255'],
            'nama_kegiatan' => ['required_if:jenis_usulan,asb', 'nullable', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'merek' => ['nullable', 'string', 'max:100'],

            'satuan' => ['required_if:jenis_usulan,ssh,sbu,hspk', 'nullable', 'string', 'max:30'],
            'harga' => ['required_if:jenis_usulan,ssh', 'nullable', 'numeric', 'min:0'],
            'besaran' => ['required_if:jenis_usulan,sbu', 'nullable', 'numeric', 'min:0'],
            'kategori' => ['required_if:jenis_usulan,sbu', 'nullable', 'in:'.implode(',', array_keys(SbuItem::KATEGORI))],
            'wilayah' => ['nullable', 'string', 'max:255'],
            'dasar_penetapan' => ['nullable', 'string', 'max:255'],

            'jenis_pekerjaan' => ['nullable', 'string', 'max:255'],
            'kelompok_kegiatan' => ['nullable', 'string', 'max:255'],
            'satuan_variabel' => ['nullable', 'string', 'max:30'],

            'sumber_harga' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $tahunAktif = TahunAnggaran::aktif();
        $jenis = $validated['jenis_usulan'];

        $dataUsulan = match ($jenis) {
            'sbu' => [
                'uraian' => $validated['uraian'],
                'kategori' => $validated['kategori'],
                'satuan' => $validated['satuan'],
                'wilayah' => $validated['wilayah'] ?? null,
                'besaran' => $validated['besaran'] ?? 0,
                'dasar_penetapan' => $validated['dasar_penetapan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'tahun_anggaran_id' => $tahunAktif?->id,
            ],
            'hspk' => [
                'uraian' => $validated['uraian'],
                'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? null,
                'satuan' => $validated['satuan'],
                'catatan' => $validated['keterangan'] ?? null,
                'tahun_anggaran_id' => $tahunAktif?->id,
            ],
            'asb' => [
                'nama_kegiatan' => $validated['nama_kegiatan'],
                'kelompok_kegiatan' => $validated['kelompok_kegiatan'] ?? null,
                'satuan_variabel' => $validated['satuan_variabel'] ?? null,
                'catatan' => $validated['keterangan'] ?? null,
                'tahun_anggaran_id' => $tahunAktif?->id,
            ],
            default => [
                'uraian' => $validated['uraian'],
                'spesifikasi' => $validated['spesifikasi'] ?? null,
                'merek' => $validated['merek'] ?? null,
                'satuan' => $validated['satuan'],
                'harga' => $validated['harga'] ?? 0,
                'sumber_harga' => $validated['sumber_harga'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'tahun_anggaran_id' => $tahunAktif?->id,
            ],
        };

        $proposal = $this->workflowService->createProposal(
            $user->opd_id,
            $jenis,
            $validated['tipe_perubahan'],
            $validated['alasan_usulan'],
            [['existing_item_id' => $validated['existing_item_id'] ?? null, 'data_usulan' => $dataUsulan]],
            $user,
        );

        return redirect()->route('opd.usulan.show', $proposal)->with('status', "Usulan {$proposal->nomor_usulan} berhasil diajukan.");
    }

    /**
     * Usulan nonaktifkan tidak butuh OPD mengetik ulang data — cukup pilih item lalu ambil
     * snapshot datanya saat ini untuk ditampilkan di layar verifikasi (Bab 12 kajian: AKTIF -> NONAKTIF).
     */
    private function storeNonaktif(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_usulan' => ['required', 'in:ssh,sbu,hspk,asb'],
            'alasan_usulan' => ['required', 'string'],
            'existing_item_id' => ['required', 'integer'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $existing = ItemTypeResolver::resolve($validated['jenis_usulan'], $validated['existing_item_id']);
        abort_if(! $existing, 404, 'Data yang diusulkan untuk dinonaktifkan tidak ditemukan.');

        $snapshot = array_intersect_key($existing->toArray(), array_flip([
            'uraian', 'nama_kegiatan', 'spesifikasi', 'merek', 'satuan', 'harga', 'besaran', 'harga_satuan',
            'hasil_perhitungan', 'kelompok_kegiatan',
        ]));
        $snapshot['keterangan'] = $validated['keterangan'] ?? null;

        $proposal = $this->workflowService->createProposal(
            $user->opd_id,
            $validated['jenis_usulan'],
            'nonaktif',
            $validated['alasan_usulan'],
            [['existing_item_id' => $validated['existing_item_id'], 'data_usulan' => $snapshot]],
            $user,
        );

        return redirect()->route('opd.usulan.show', $proposal)->with('status', "Usulan nonaktifkan {$proposal->nomor_usulan} berhasil diajukan.");
    }

    public function cekSerupa(Request $request): JsonResponse
    {
        $validated = $request->validate(['uraian' => ['required', 'string'], 'merek' => ['nullable', 'string']]);

        return response()->json(['hasil' => $this->duplicateDetectionService->findSimilar($validated['uraian'], $validated['merek'] ?? null)->values()]);
    }

    /** Pencarian item aktif untuk dijadikan target usulan "perubahan"/"nonaktifkan" (Bab 11 kajian). */
    public function cariItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:ssh,sbu,hspk,asb'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = $validated['q'] ?? '';

        $hasil = match ($validated['jenis']) {
            'sbu' => SbuItem::active()->when($term, fn ($q) => $q->where('uraian', 'ilike', "%{$term}%"))->limit(15)->get()
                ->map(fn (SbuItem $i) => [
                    'id' => $i->id, 'label' => "{$i->uraian} — Rp ".number_format((float) $i->besaran, 0, ',', '.'),
                    'uraian' => $i->uraian, 'kategori' => $i->kategori, 'satuan' => $i->satuan,
                    'wilayah' => $i->wilayah, 'besaran' => (float) $i->besaran, 'dasar_penetapan' => $i->dasar_penetapan,
                ]),
            'hspk' => Hspk::active()->when($term, fn ($q) => $q->where('uraian', 'ilike', "%{$term}%"))->limit(15)->get()
                ->map(fn (Hspk $i) => [
                    'id' => $i->id, 'label' => "{$i->uraian} — Rp ".number_format((float) $i->harga_satuan, 0, ',', '.'),
                    'uraian' => $i->uraian, 'jenis_pekerjaan' => $i->jenis_pekerjaan, 'satuan' => $i->satuan,
                ]),
            'asb' => Asb::active()->when($term, fn ($q) => $q->where('nama_kegiatan', 'ilike', "%{$term}%"))->limit(15)->get()
                ->map(fn (Asb $i) => [
                    'id' => $i->id, 'label' => "{$i->nama_kegiatan} — Rp ".number_format((float) $i->hasil_perhitungan, 0, ',', '.'),
                    'nama_kegiatan' => $i->nama_kegiatan, 'kelompok_kegiatan' => $i->kelompok_kegiatan, 'satuan_variabel' => $i->satuan_variabel,
                ]),
            default => SshItem::active()->when($term, fn ($q) => $q->search($term))->limit(15)->get()
                ->map(fn (SshItem $i) => [
                    'id' => $i->id, 'label' => "{$i->uraian} ({$i->merek}) — Rp ".number_format((float) $i->harga, 0, ',', '.'),
                    'uraian' => $i->uraian, 'spesifikasi' => $i->spesifikasi, 'merek' => $i->merek,
                    'satuan' => $i->satuan, 'harga' => (float) $i->harga, 'sumber_harga' => $i->sumber_harga,
                ]),
        };

        return response()->json(['hasil' => $hasil->values()]);
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
