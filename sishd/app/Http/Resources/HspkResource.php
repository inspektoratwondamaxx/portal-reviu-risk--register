<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HspkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'uraian' => $this->uraian,
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'satuan' => $this->satuan,
            'harga_satuan' => (float) $this->harga_satuan,
            'tahun_anggaran' => $this->whenLoaded('tahunAnggaran', fn () => $this->tahunAnggaran?->tahun),
            'komponen' => HspkComponentResource::collection($this->whenLoaded('components')),
            'terakhir_dihitung' => $this->last_calculated_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
