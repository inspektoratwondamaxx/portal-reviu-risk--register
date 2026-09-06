<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SbuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'kategori' => $this->kategori,
            'uraian' => $this->uraian,
            'satuan' => $this->satuan,
            'wilayah' => $this->wilayah,
            'besaran' => (float) $this->besaran,
            'dasar_penetapan' => $this->dasar_penetapan,
            'opd' => $this->whenLoaded('opd', fn () => $this->opd ? [
                'kode' => $this->opd->kode,
                'nama' => $this->opd->nama,
                'singkatan' => $this->opd->singkatan,
            ] : null),
            'tahun_anggaran' => $this->whenLoaded('tahunAnggaran', fn () => $this->tahunAnggaran?->tahun),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
