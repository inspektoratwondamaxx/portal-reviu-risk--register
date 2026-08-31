<?php

namespace App\Services;

use App\Enums\MappingStatus;
use App\Models\AssetCode;
use App\Models\CodeMapping;

/**
 * Validasi mapping Kode Aset -> Kode Rekening -> Kode SIPD (Bab 10 kajian) — inti masalah yang
 * diselesaikan aplikasi: kode/struktur database berbeda antar-OPD dikonsolidasikan lewat tabel ini.
 * Status dihitung ulang setiap dipanggil, bukan sekali simpan lalu basi.
 */
class CodeMappingValidationService
{
    /** @return array<string, int> ringkasan jumlah per status, dipakai kartu di halaman Validasi Mapping. */
    public function validateAll(): array
    {
        $summary = ['valid' => 0, 'belum_rekening' => 0, 'duplikasi' => 0];

        $grouped = CodeMapping::query()->get()->groupBy('asset_code_id');

        foreach ($grouped as $mappings) {
            if ($mappings->count() > 1) {
                foreach ($mappings as $mapping) {
                    $this->applyStatus($mapping, MappingStatus::Duplikasi);
                }
                $summary['duplikasi'] += $mappings->count();

                continue;
            }

            $mapping = $mappings->first();
            $status = $mapping->account_code_id ? MappingStatus::Valid : MappingStatus::BelumRekening;
            $this->applyStatus($mapping, $status);
            $summary[$status->value]++;
        }

        $belumDipetakan = AssetCode::query()
            ->where('is_active', true)
            ->whereDoesntHave('codeMappings')
            ->count();

        return $summary + ['tidak_ditemukan' => $belumDipetakan];
    }

    /** Cek satu kode aset ad-hoc (dipakai form pencarian cepat di halaman Mapping). */
    public function checkKode(string $kode): array
    {
        $assetCode = AssetCode::where('kode', $kode)->first();

        if (! $assetCode) {
            return ['status' => MappingStatus::TidakDitemukan, 'asset_code' => null, 'mappings' => collect()];
        }

        $mappings = CodeMapping::where('asset_code_id', $assetCode->id)->get();

        if ($mappings->isEmpty()) {
            return ['status' => MappingStatus::TidakDitemukan, 'asset_code' => $assetCode, 'mappings' => $mappings];
        }

        if ($mappings->count() > 1) {
            return ['status' => MappingStatus::Duplikasi, 'asset_code' => $assetCode, 'mappings' => $mappings];
        }

        $status = $mappings->first()->account_code_id ? MappingStatus::Valid : MappingStatus::BelumRekening;

        return ['status' => $status, 'asset_code' => $assetCode, 'mappings' => $mappings];
    }

    private function applyStatus(CodeMapping $mapping, MappingStatus $status): CodeMapping
    {
        $mapping->forceFill(['status' => $status, 'checked_at' => now()])->save();

        return $mapping;
    }
}
