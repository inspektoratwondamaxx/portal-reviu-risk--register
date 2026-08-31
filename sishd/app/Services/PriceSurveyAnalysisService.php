<?php

namespace App\Services;

use App\Models\PriceSurvey;
use Illuminate\Support\Collection;

/** Rekap survei harga (Bab 15 kajian): harga minimum/maksimum/median/rata-rata/rekomendasi. */
class PriceSurveyAnalysisService
{
    public function forSshItem(int $sshItemId): array
    {
        return $this->summarize(PriceSurvey::where('ssh_item_id', $sshItemId)->pluck('harga'));
    }

    public function forUraian(string $uraian): array
    {
        return $this->summarize(PriceSurvey::where('uraian_barang', 'ilike', "%{$uraian}%")->pluck('harga'));
    }

    /** @param  Collection<int, mixed>  $hargaList */
    public function summarize(Collection $hargaList): array
    {
        $harga = $hargaList->map(fn ($h) => (float) $h)->sort()->values();

        if ($harga->isEmpty()) {
            return ['jumlah' => 0, 'min' => 0.0, 'max' => 0.0, 'median' => 0.0, 'rata_rata' => 0.0, 'rekomendasi' => 0.0];
        }

        $count = $harga->count();
        $mid = intdiv($count, 2);
        $median = $count % 2 === 0 ? ($harga[$mid - 1] + $harga[$mid]) / 2 : $harga[$mid];

        return [
            'jumlah' => $count,
            'min' => $harga->first(),
            'max' => $harga->last(),
            'median' => round($median, 2),
            'rata_rata' => round($harga->avg(), 2),
            // Median dipakai sebagai harga rekomendasi karena lebih tahan outlier dibanding rata-rata.
            'rekomendasi' => round($median, 2),
        ];
    }
}
