<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PeriodeSpj;
use App\Models\Transaksi;
use App\Services\BkuSpjPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PeriodeSpjController extends Controller
{
    public function __construct(private readonly BkuSpjPdfService $pdfService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PeriodeSpj::class);

        $query = PeriodeSpj::query()->with('kampung');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $periodeSpj = $query->orderByDesc('tahun_anggaran')->orderByDesc('bulan')->paginate(20)->withQueryString();

        return view('spj.index', compact('periodeSpj'));
    }

    public function show(PeriodeSpj $periodeSpj)
    {
        $this->authorize('view', $periodeSpj);

        $periodeSpj->load([
            'kampung',
            'transaksis.kodeRekening',
            'transaksis.kegiatan',
            'riwayatStatus.pengubah',
            'dokumen',
        ]);

        return view('spj.show', compact('periodeSpj'));
    }

    public function ajukan(PeriodeSpj $periodeSpj)
    {
        $this->authorize('ajukan', $periodeSpj);

        DB::transaction(function () use ($periodeSpj) {
            $transaksis = Transaksi::query()
                ->where('kampung_id', $periodeSpj->kampung_id)
                ->where('status', Transaksi::STATUS_TERVERIFIKASI)
                ->whereYear('tanggal_transaksi', $periodeSpj->tahun_anggaran)
                ->whereMonth('tanggal_transaksi', $periodeSpj->bulan)
                ->orderBy('tanggal_transaksi')
                ->get();

            foreach ($transaksis as $urutan => $transaksi) {
                $periodeSpj->transaksis()->syncWithoutDetaching([$transaksi->id => ['urutan_bku' => $urutan + 1]]);
                $transaksi->ubahStatus(Transaksi::STATUS_DIAJUKAN, 'Diajukan bersama periode SPJ.');
            }

            $periodeSpj->update(['saldo_akhir' => $periodeSpj->hitungSaldoAkhir()]);
            $periodeSpj->ubahStatus(PeriodeSpj::STATUS_DIAJUKAN, 'Diajukan untuk diperiksa.');
        });

        return back()->with('status', 'Periode SPJ berhasil diajukan.');
    }

    public function setujui(Request $request, PeriodeSpj $periodeSpj)
    {
        $this->authorize('setujui', $periodeSpj);

        $catatan = $request->input('catatan') ?: null;
        $statusBerikutnya = $periodeSpj->status === PeriodeSpj::STATUS_DIAJUKAN
            ? PeriodeSpj::STATUS_DISETUJUI_PENDAMPING
            : PeriodeSpj::STATUS_DISETUJUI_INSPEKTORAT;

        $this->cascadeStatusTransaksi($periodeSpj, $statusBerikutnya, $catatan);
        $periodeSpj->ubahStatus($statusBerikutnya, $catatan);

        return back()->with('status', 'SPJ disetujui.');
    }

    public function tolak(Request $request, PeriodeSpj $periodeSpj)
    {
        $this->authorize('tolak', $periodeSpj);

        $request->validate(['catatan' => ['required', 'string']]);

        $this->cascadeStatusTransaksi($periodeSpj, Transaksi::STATUS_REVISI, $request->input('catatan'));
        $periodeSpj->ubahStatus(PeriodeSpj::STATUS_REVISI, $request->input('catatan'));

        return back()->with('status', 'SPJ dikembalikan untuk revisi.');
    }

    public function generatePdf(PeriodeSpj $periodeSpj)
    {
        $this->authorize('generatePdf', $periodeSpj);

        if ($periodeSpj->status !== PeriodeSpj::STATUS_DISETUJUI_INSPEKTORAT) {
            return back()->withErrors(['spj' => 'Dokumen SPJ hanya dapat dibuat setelah disetujui Inspektorat.']);
        }

        $this->pdfService->generate($periodeSpj);
        $this->cascadeStatusTransaksi($periodeSpj, Transaksi::STATUS_FINAL, 'Dokumen SPJ final diterbitkan.');
        $periodeSpj->ubahStatus(PeriodeSpj::STATUS_FINAL, 'Dokumen SPJ diterbitkan.');

        return back()->with('status', 'Dokumen SPJ berhasil dibuat.');
    }

    public function unduhPdf(PeriodeSpj $periodeSpj)
    {
        $this->authorize('view', $periodeSpj);

        $dokumen = $periodeSpj->dokumenTerbaru();

        abort_if(! $dokumen, 404, 'Dokumen SPJ belum tersedia.');

        return Storage::disk('bukti')->download($dokumen->path_pdf, "SPJ-{$periodeSpj->kampung->kode_kampung}-{$periodeSpj->tahun_anggaran}-{$periodeSpj->bulan}.pdf");
    }

    public function exportSiskeudes(PeriodeSpj $periodeSpj)
    {
        $this->authorize('exportSiskeudes', $periodeSpj);

        $periodeSpj->loadMissing(['transaksis.kodeRekening', 'transaksis.kegiatan']);
        $filename = "siskeudes_{$periodeSpj->kampung_id}_{$periodeSpj->tahun_anggaran}_{$periodeSpj->bulan}.csv";

        return response()->streamDownload(function () use ($periodeSpj) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'kode_rekening', 'uraian_rekening', 'kegiatan', 'uraian_transaksi', 'nominal']);

            foreach ($periodeSpj->transaksis as $t) {
                fputcsv($out, [
                    $t->tanggal_transaksi->format('Y-m-d'),
                    $t->kodeRekening->kode,
                    $t->kodeRekening->uraian,
                    $t->kegiatan->nama_kegiatan,
                    $t->uraian,
                    $t->nominal,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function cascadeStatusTransaksi(PeriodeSpj $periodeSpj, string $status, ?string $catatan): void
    {
        foreach ($periodeSpj->transaksis as $transaksi) {
            $transaksi->ubahStatus($status, $catatan);
        }
    }
}
