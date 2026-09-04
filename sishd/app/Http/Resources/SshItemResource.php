<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SshItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_barang' => $this->kode_barang,
            'uraian' => $this->uraian,
            'spesifikasi' => $this->spesifikasi,
            'merek' => $this->merek,
            'satuan' => $this->satuan,
            'harga' => (float) $this->harga,
            'sumber_harga' => $this->sumber_harga,
            'kategori' => $this->whenLoaded('category', fn () => [
                'kode' => $this->category?->kode,
                'nama' => $this->category?->nama,
            ]),
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
