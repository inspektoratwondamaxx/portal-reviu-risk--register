<?php

namespace App\Services;

use App\Contracts\NarasiAiGenerator;
use App\Models\Transaksi;
use Illuminate\Support\Number;

/**
 * Implementasi fallback tanpa layanan AI eksternal: menyusun narasi dari data terstruktur
 * transaksi (uraian rekening, nama kegiatan, tanggal, nominal). Selalu berstatus draft (KNF-10)
 * — Kaur Keuangan tetap wajib meninjau/menyunting sebelum disimpan final.
 */
class TemplateNarasiAiGenerator implements NarasiAiGenerator
{
    public function susunNarasi(Transaksi $transaksi): string
    {
        $transaksi->loadMissing(['kegiatan', 'kodeRekening']);

        $tanggal = $transaksi->tanggal_transaksi?->translatedFormat('d F Y') ?? '-';
        $nominal = Number::currency((float) $transaksi->nominal, 'IDR', 'id');

        return sprintf(
            'Pembayaran %s untuk kegiatan "%s" pada tanggal %s sebesar %s.',
            $transaksi->kodeRekening?->uraian ?? 'belanja',
            $transaksi->kegiatan?->nama_kegiatan ?? '-',
            $tanggal,
            $nominal,
        );
    }
}
