<?php

namespace App\Services;

use App\Models\Hspk;
use App\Models\HspkAnalysis;
use App\Models\HspkComponent;
use Illuminate\Support\Facades\Auth;

/**
 * Mesin penghitungan ulang HSPK (Bab 8 & 20 kajian): "kalau harga semen berubah di master SSH,
 * sistem menghitung ulang HSPK secara otomatis". Dipanggil oleh Concerns\HasPriceHistory setiap
 * kali harga SSH/SBU berubah, lalu berantai ke AsbCalculationService untuk ASB yang bergantung
 * pada HSPK tsb — menjadikan SSH -> HSPK -> ASB satu data ecosystem, bukan silo terpisah.
 */
class HspkCalculationService
{
    public function __construct(private readonly AsbCalculationService $asbCalculationService)
    {
    }

    public function recalculate(Hspk $hspk, ?string $pemicu = null, ?string $pemicuType = null, ?int $pemicuId = null): Hspk
    {
        $before = (float) $hspk->harga_satuan;
        $total = 0.0;

        foreach ($hspk->components()->with(['sshItem', 'sbuItem'])->get() as $component) {
            $hargaSatuan = $this->resolveComponentPrice($component);
            $subtotal = round((float) $component->koefisien * $hargaSatuan, 2);

            if (abs((float) $component->harga_satuan - $hargaSatuan) > 0.0001 || abs((float) $component->subtotal - $subtotal) > 0.0001) {
                $component->forceFill(['harga_satuan' => $hargaSatuan, 'subtotal' => $subtotal])->save();
            }

            $total += $subtotal;
        }

        $total = round($total, 2);
        $berubah = abs($total - $before) > 0.0001;

        $hspk->forceFill([
            'harga_satuan' => $total,
            'last_calculated_at' => now(),
        ])->save();

        if ($berubah) {
            $persentase = abs($before) > 0.0001 ? round((($total - $before) / $before) * 100, 2) : 0.0;

            HspkAnalysis::create([
                'hspk_id' => $hspk->id,
                'harga_sebelum' => $before,
                'harga_sesudah' => $total,
                'selisih' => round($total - $before, 2),
                'persentase' => $persentase,
                'pemicu' => $pemicu,
                'pemicu_type' => $pemicuType,
                'pemicu_id' => $pemicuId,
                'dihitung_oleh' => Auth::id(),
                'dihitung_pada' => now(),
            ]);

            // Berantai: ASB yang variabelnya bersumber dari HSPK ini ikut dihitung ulang.
            $this->asbCalculationService->recalculateForSource('hspk', $hspk->id);
        }

        return $hspk->refresh();
    }

    /**
     * Dipanggil saat harga sumber (ssh_item/sbu_item) berubah: cari semua HSPK yang memakainya
     * sebagai komponen lalu hitung ulang satu per satu.
     */
    public function recalculateForSource(string $itemType, int $itemId, ?string $pemicuLabel = null): int
    {
        $column = $itemType === 'sbu' ? 'sbu_item_id' : 'ssh_item_id';

        $hspkIds = HspkComponent::query()->where($column, $itemId)->distinct()->pluck('hspk_id');

        $affected = 0;
        foreach (Hspk::query()->whereIn('id', $hspkIds)->get() as $hspk) {
            $this->recalculate($hspk, $pemicuLabel, $itemType, $itemId);
            $affected++;
        }

        return $affected;
    }

    /**
     * Sumber harga ditentukan dari foreign key yang benar-benar terisi, bukan dari komponen_type —
     * tenaga kerja maupun peralatan (mis. sewa alat) sama-sama lazim bersumber dari SBU, sementara
     * material bersumber dari SSH. Ini menjaga kombinasi apa pun (mis. peralatan dari SBU) tetap
     * terhitung, bukan diam-diam macet di 0.
     */
    private function resolveComponentPrice(HspkComponent $component): float
    {
        if ($component->sbu_item_id) {
            return (float) ($component->sbuItem?->besaran ?? $component->harga_satuan);
        }

        if ($component->ssh_item_id) {
            return (float) ($component->sshItem?->harga ?? $component->harga_satuan);
        }

        return (float) $component->harga_satuan;
    }
}
