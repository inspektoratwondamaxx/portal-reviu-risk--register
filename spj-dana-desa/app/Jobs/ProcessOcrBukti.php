<?php

namespace App\Jobs;

use App\Models\BuktiTransaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * KF-05 (OCR job asinkron, KNF-06). Belum terhubung ke layanan OCR pihak ketiga sungguhan —
 * lihat README "Yang Belum Diimplementasikan". Job ini sengaja mengimplementasikan strategi
 * fallback yang diminta Bab VII.3: bila layanan OCR tidak tersedia, bukti_transaksi ditandai
 * gagal diproses agar Kaur Keuangan mengisi data secara manual, alih-alih membuat request
 * menggantung tanpa status.
 */
class ProcessOcrBukti implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $buktiTransaksiId) {}

    public function handle(): void
    {
        $bukti = BuktiTransaksi::find($this->buktiTransaksiId);

        if (! $bukti) {
            return;
        }

        $bukti->update([
            'status_ocr' => BuktiTransaksi::OCR_GAGAL,
            'hasil_ocr_raw' => [
                'stub' => true,
                'pesan' => 'Layanan OCR pihak ketiga belum dikonfigurasi pada tahap ini. '
                    .'Silakan isi/koreksi data transaksi secara manual melalui endpoint verifikasi-ocr.',
            ],
        ]);
    }
}
