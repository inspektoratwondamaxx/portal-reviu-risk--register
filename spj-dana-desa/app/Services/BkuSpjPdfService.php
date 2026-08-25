<?php

namespace App\Services;

use App\Models\PeriodeSpj;
use App\Models\SpjDokumen;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Bab IV.2 & KF-10/KF-11/KF-12: merangkai transaksi final periode menjadi Buku Kas Umum (BKU)
 * dan lampiran bukti, lalu menghasilkan dokumen SPJ PDF siap cetak.
 *
 * Catatan cakupan data: skema 15 tabel pada kajian teknis (Bab V.2) hanya memodelkan transaksi
 * belanja (pengeluaran) — tidak ada entitas pencairan/penerimaan dana dari RKD. BKU yang
 * dihasilkan karena itu berupa rekapitulasi realisasi belanja kumulatif, bukan buku kas dua
 * sisi (penerimaan-pengeluaran) penuh. Skema perlu ditambah entitas pencairan dana bila BKU
 * dua sisi diperlukan untuk pelaporan resmi — lihat README "Catatan Ambiguitas Dokumen".
 */
class BkuSpjPdfService
{
    public function generate(PeriodeSpj $periode): SpjDokumen
    {
        $periode->loadMissing(['kampung', 'transaksis' => function ($query) {
            $query->with(['kegiatan', 'kodeRekening', 'buktiTransaksi'])->orderBy('tanggal_transaksi');
        }]);

        $saldoBerjalan = 0.0;
        $baris = $periode->transaksis->map(function ($transaksi) use (&$saldoBerjalan) {
            $saldoBerjalan = round($saldoBerjalan + (float) $transaksi->nominal, 2);

            return [
                'tanggal' => $transaksi->tanggal_transaksi->format('d-m-Y'),
                'kode_rekening' => $transaksi->kodeRekening->kode,
                'uraian_rekening' => $transaksi->kodeRekening->uraian,
                'kegiatan' => $transaksi->kegiatan->nama_kegiatan,
                'uraian' => $transaksi->uraian,
                'nominal' => $transaksi->nominal,
                'saldo_berjalan' => $saldoBerjalan,
                'jumlah_bukti' => $transaksi->buktiTransaksi->count(),
            ];
        });

        $rekapPerRekening = $periode->transaksis
            ->groupBy(fn ($t) => $t->kodeRekening->kode)
            ->map(fn ($group) => [
                'kode' => $group->first()->kodeRekening->kode,
                'uraian' => $group->first()->kodeRekening->uraian,
                'total' => $group->sum('nominal'),
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.spj-dokumen', [
            'periode' => $periode,
            'baris' => $baris,
            'rekap' => $rekapPerRekening,
            'totalBelanja' => $saldoBerjalan,
            'namaBulan' => now()->setDate($periode->tahun_anggaran, $periode->bulan, 1)->translatedFormat('F'),
        ])->setPaper('a4', 'portrait');

        $versi = ((int) $periode->dokumen()->max('versi')) + 1;
        $path = "{$periode->kampung_id}/{$periode->tahun_anggaran}/spj/periode-{$periode->id}-v{$versi}.pdf";

        Storage::disk('bukti')->put($path, $pdf->output());

        $dokumen = $periode->dokumen()->create([
            'path_pdf' => $path,
            'versi' => $versi,
            'generated_by' => Auth::id(),
        ]);

        $periode->update(['saldo_akhir' => $saldoBerjalan]);

        return $dokumen;
    }
}
