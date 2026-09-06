<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsbResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'nama_kegiatan' => $this->nama_kegiatan,
            'kelompok_kegiatan' => $this->kelompok_kegiatan,
            'satuan_variabel' => $this->satuan_variabel,
            'batas_minimal' => (float) $this->batas_minimal,
            'batas_maksimal' => (float) $this->batas_maksimal,
            'hasil_perhitungan' => (float) $this->hasil_perhitungan,
            'tahun_anggaran' => $this->whenLoaded('tahunAnggaran', fn () => $this->tahunAnggaran?->tahun),
            'variabel' => AsbVariableResource::collection($this->whenLoaded('variables')),
            'formula' => $this->whenLoaded('formula', fn () => $this->formula?->ekspresi),
            'terakhir_dihitung' => $this->last_calculated_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
