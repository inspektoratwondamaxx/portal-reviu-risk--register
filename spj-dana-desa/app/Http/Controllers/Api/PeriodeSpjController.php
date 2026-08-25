<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodeSpj;
use App\Models\Transaksi;
use App\Services\BkuSpjPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Bab VI.5 kajian teknis. Lihat App\Policies\PeriodeSpjPolicy::setujui() untuk catatan
 * ketidaksesuaian antara tabel peran Bab IV.3 dan tabel endpoint Bab VI.5 seputar kepala_kampung.
 *
 * Catatan interpretasi: status transaksi individual (draft/terverifikasi/diajukan/...) dan
 * status periode_spj bulanan sama-sama disebut di kajian teknis tanpa penjelasan eksplisit
 * bagaimana keduanya berelasi. Implementasi ini menjadikan periode_spj sebagai unit persetujuan
 * sesungguhnya: saat kaur mengajukan periode, seluruh transaksi berstatus "terverifikasi" pada
 * bulan tsb dirangkai ke periode dan ikut berpindah status secara berjenjang mengikuti periode.
 */
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

        if ($tahun = $request->query('tahun')) {
            $query->where('tahun_anggaran', $tahun);
        }

        return $this->ok($query->orderByDesc('tahun_anggaran')->orderByDesc('bulan')->paginate(20)->items());
    }

    public function show(PeriodeSpj $periodeSpj)
    {
        $this->authorize('view', $periodeSpj);

        return $this->ok($periodeSpj->load([
            'kampung',
            'transaksis.kodeRekening',
            'transaksis.kegiatan',
            'riwayatStatus.pengubah',
            'dokumen',
        ]));
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
                $periodeSpj->transaksis()->syncWithoutDetaching([
                    $transaksi->id => ['urutan_bku' => $urutan + 1],
                ]);
                $transaksi->ubahStatus(Transaksi::STATUS_DIAJUKAN, 'Diajukan bersama periode SPJ.');
            }

            $periodeSpj->update(['saldo_akhir' => $periodeSpj->hitungSaldoAkhir()]);
            $periodeSpj->ubahStatus(PeriodeSpj::STATUS_DIAJUKAN, 'Diajukan untuk diperiksa.');
        });

        return $this->ok($periodeSpj->fresh(['transaksis']));
    }

    public function setujui(Request $request, PeriodeSpj $periodeSpj)
    {
        $this->authorize('setujui', $periodeSpj);

        $catatan = $request->string('catatan')->toString() ?: null;
        $statusBerikutnya = $periodeSpj->status === PeriodeSpj::STATUS_DIAJUKAN
            ? PeriodeSpj::STATUS_DISETUJUI_PENDAMPING
            : PeriodeSpj::STATUS_DISETUJUI_INSPEKTORAT;

        $this->cascadeStatusTransaksi($periodeSpj, $statusBerikutnya, $catatan);
        $periodeSpj->ubahStatus($statusBerikutnya, $catatan);

        return $this->ok($periodeSpj->fresh());
    }

    public function tolak(Request $request, PeriodeSpj $periodeSpj)
    {
        $this->authorize('tolak', $periodeSpj);

        $validator = Validator::make($request->all(), [
            'catatan' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Catatan penolakan wajib diisi.', $validator->errors()->toArray());
        }

        $catatan = $request->string('catatan')->toString();

        $this->cascadeStatusTransaksi($periodeSpj, Transaksi::STATUS_REVISI, $catatan);
        $periodeSpj->ubahStatus(PeriodeSpj::STATUS_REVISI, $catatan);

        return $this->ok($periodeSpj->fresh());
    }

    public function generatePdf(PeriodeSpj $periodeSpj)
    {
        $this->authorize('generatePdf', $periodeSpj);

        if ($periodeSpj->status !== PeriodeSpj::STATUS_DISETUJUI_INSPEKTORAT) {
            return $this->fail('Dokumen SPJ hanya dapat dibuat setelah disetujui Inspektorat.', status: 422);
        }

        $dokumen = $this->pdfService->generate($periodeSpj);

        $this->cascadeStatusTransaksi($periodeSpj, Transaksi::STATUS_FINAL, 'Dokumen SPJ final diterbitkan.');
        $periodeSpj->ubahStatus(PeriodeSpj::STATUS_FINAL, 'Dokumen SPJ diterbitkan.');

        return $this->ok($dokumen, status: 201);
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
