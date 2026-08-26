<?php

namespace App\Contracts;

use App\Models\Transaksi;

/**
 * Kontrak penyusun narasi kegiatan otomatis (KF-07). Implementasi bawaan
 * (TemplateNarasiAiGenerator) adalah fallback deterministik tanpa layanan AI eksternal — lihat
 * README bagian "Yang Belum Diimplementasikan" untuk rencana integrasi penyedia LLM sungguhan
 * pada Tahap 3. Ganti binding di AppServiceProvider untuk memasang penyedia AI nyata.
 */
interface NarasiAiGenerator
{
    public function susunNarasi(Transaksi $transaksi): string;
}
