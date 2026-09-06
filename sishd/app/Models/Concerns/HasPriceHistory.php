<?php

namespace App\Models\Concerns;

use App\Models\PriceHistory;
use App\Services\HspkCalculationService;
use Illuminate\Support\Facades\Auth;

/**
 * Dipasang di SshItem & SbuItem. Setiap perubahan kolom harga (Bab 16 kajian) otomatis:
 *  1) dicatat ke price_histories (harga lama/baru, persentase, dasar perubahan);
 *  2) memicu HspkCalculationService untuk menghitung ulang seluruh HSPK yang memakai item ini
 *     sebagai komponen, dan berantai ke ASB yang variabelnya bersumber dari HSPK tsb
 *     (Bab 8 & 20 kajian: "satu data ecosystem", bukan database terpisah).
 *
 * pendingDasarPerubahan/pendingKeterangan adalah properti transient (bukan kolom tabel) yang bisa
 * diisi controller sebelum save() untuk mencatat konteks perubahan, mis. "Survei harga" atau
 * "Import Excel".
 */
trait HasPriceHistory
{
    public ?string $pendingDasarPerubahan = null;

    public ?string $pendingKeterangan = null;

    public static function bootHasPriceHistory(): void
    {
        static::updated(function ($model) {
            $column = $model->priceColumn();

            if (! $model->wasChanged($column)) {
                return;
            }

            $lama = (float) $model->getOriginal($column);
            $baru = (float) $model->getAttribute($column);

            if (abs($lama - $baru) < 0.0001) {
                return;
            }

            $persentase = abs($lama) > 0.0001 ? round((($baru - $lama) / $lama) * 100, 2) : 0.0;

            PriceHistory::create([
                'item_type' => $model->priceItemType(),
                'item_id' => $model->getKey(),
                'harga_lama' => $lama,
                'harga_baru' => $baru,
                'persentase' => $persentase,
                'dasar_perubahan' => $model->pendingDasarPerubahan,
                'keterangan' => $model->pendingKeterangan,
                'user_id' => Auth::id(),
                'tanggal' => now()->toDateString(),
            ]);

            app(HspkCalculationService::class)->recalculateForSource(
                $model->priceItemType(),
                $model->getKey(),
                "Perubahan harga {$model->priceItemType()}: {$model->uraian}"
            );
        });
    }

    abstract public function priceColumn(): string;

    abstract public function priceItemType(): string;
}
