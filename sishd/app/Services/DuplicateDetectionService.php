<?php

namespace App\Services;

use App\Models\SshItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Deteksi barang serupa (Bab 14 kajian): "DATA SERUPA DITEMUKAN" saat OPD mengetik uraian barang.
 * Memakai fungsi similarity() dari ekstensi PostgreSQL pg_trgm (bukan hanya exact match) sehingga
 * "Semen Portland 40 Kg" tetap terdeteksi mirip "Semen Portland 40Kg" atau beda merek/ukuran.
 */
class DuplicateDetectionService
{
    public function findSimilar(string $uraian, ?string $merek = null, float $threshold = 0.35, int $limit = 5): Collection
    {
        $uraian = trim($uraian);

        if ($uraian === '') {
            return collect();
        }

        if (DB::getDriverName() !== 'pgsql') {
            return SshItem::query()
                ->where('is_active', true)
                ->where('uraian', 'like', '%'.$uraian.'%')
                ->limit($limit)
                ->get()
                ->map(fn (SshItem $item) => $this->toResult($item, null, $merek));
        }

        $rows = DB::table('ssh_items')
            ->selectRaw('id, similarity(uraian, ?) as skor', [$uraian])
            ->where('is_active', true)
            ->whereRaw('similarity(uraian, ?) > ?', [$uraian, $threshold])
            ->orderByDesc('skor')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) use ($merek) {
            $item = SshItem::find($row->id);

            return $item ? $this->toResult($item, round(((float) $row->skor) * 100, 1), $merek) : null;
        })->filter()->values();
    }

    private function toResult(SshItem $item, ?float $skor, ?string $merek): array
    {
        return [
            'id' => $item->id,
            'kode_barang' => $item->kode_barang,
            'uraian' => $item->uraian,
            'spesifikasi' => $item->spesifikasi,
            'merek' => $item->merek,
            'harga' => (float) $item->harga,
            'skor' => $skor,
            'merek_sama' => $merek && $item->merek && mb_strtolower($item->merek) === mb_strtolower($merek),
        ];
    }
}
