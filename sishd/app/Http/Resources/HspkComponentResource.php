<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HspkComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'jenis' => $this->komponen_type?->value,
            'uraian' => $this->uraian,
            'koefisien' => (float) $this->koefisien,
            'satuan' => $this->satuan,
            'harga_satuan' => (float) $this->harga_satuan,
            'subtotal' => (float) $this->subtotal,
            'sumber' => $this->sbu_item_id ? 'sbu' : ($this->ssh_item_id ? 'ssh' : null),
        ];
    }
}
