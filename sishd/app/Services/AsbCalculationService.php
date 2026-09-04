<?php

namespace App\Services;

use App\Models\Asb;
use App\Models\AsbVariable;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;

/**
 * Refresh nilai variabel dinamis lalu evaluasi ulang formula ASB (Bab 9 kajian). Variabel dengan
 * sumber_tipe manual tidak disentuh; variabel yang ditarik dari ssh_item/hspk/sbu_item selalu
 * disinkronkan ke harga terbaru sebelum formula dievaluasi.
 */
class AsbCalculationService
{
    public function __construct(private readonly SafeFormulaEvaluator $evaluator)
    {
    }

    public function recalculate(Asb $asb): Asb
    {
        foreach ($asb->variables as $variable) {
            $this->refreshVariableValue($variable);
        }

        $formula = $asb->formula;

        if (! $formula || trim($formula->ekspresi) === '') {
            return $asb;
        }

        $variables = $asb->variables()->get()->mapWithKeys(
            fn (AsbVariable $v) => [$v->kode_variabel => (float) $v->nilai]
        )->all();

        try {
            $hasil = round($this->evaluator->evaluate($formula->ekspresi, $variables), 2);
        } catch (FormulaEvaluationException $e) {
            $asb->forceFill(['catatan' => 'Gagal menghitung formula: '.$e->getMessage()])->save();

            return $asb;
        }

        $asb->forceFill([
            'hasil_perhitungan' => $hasil,
            'last_calculated_at' => now(),
        ])->save();

        return $asb->refresh();
    }

    /** Dipanggil saat HSPK/SSH/SBU sumber sebuah variabel ASB berubah harganya. */
    public function recalculateForSource(string $sumberTipe, int $sumberId): int
    {
        $asbIds = AsbVariable::query()
            ->where('sumber_tipe', $sumberTipe)
            ->where('sumber_id', $sumberId)
            ->distinct()
            ->pluck('asb_id');

        $affected = 0;
        foreach (Asb::query()->whereIn('id', $asbIds)->get() as $asb) {
            $this->recalculate($asb);
            $affected++;
        }

        return $affected;
    }

    private function refreshVariableValue(AsbVariable $variable): void
    {
        if ($variable->sumber_tipe === 'manual' || ! $variable->sumber_id) {
            return;
        }

        $nilaiBaru = match ($variable->sumber_tipe) {
            'ssh_item' => SshItem::find($variable->sumber_id)?->harga,
            'sbu_item' => SbuItem::find($variable->sumber_id)?->besaran,
            'hspk' => Hspk::find($variable->sumber_id)?->harga_satuan,
            default => null,
        };

        if ($nilaiBaru !== null && abs((float) $variable->nilai - (float) $nilaiBaru) > 0.0001) {
            $variable->forceFill(['nilai' => $nilaiBaru])->save();
        }
    }
}
