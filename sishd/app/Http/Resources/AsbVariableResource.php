<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsbVariableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_variabel' => $this->kode_variabel,
            'label' => $this->label,
            'nilai' => (float) $this->nilai,
            'satuan' => $this->satuan,
            'sumber_tipe' => $this->sumber_tipe,
        ];
    }
}
