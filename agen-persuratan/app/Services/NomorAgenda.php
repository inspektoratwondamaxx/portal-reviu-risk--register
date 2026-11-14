<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NomorAgenda
{
    /**
     * Mengambil nomor urut berikutnya pada tahun tertentu.
     * Harus dipanggil di dalam DB::transaction agar aman dari balapan (race condition).
     *
     * @return array{urut:int, nomor_agenda:string}
     */
    public static function berikutnya(string $tabel, string $kode, int $tahun): array
    {
        $urut = (int) DB::table($tabel)->where('tahun', $tahun)->lockForUpdate()->max('urut') + 1;

        return ['urut' => $urut, 'nomor_agenda' => sprintf('%04d/%s/%d', $urut, $kode, $tahun)];
    }
}
