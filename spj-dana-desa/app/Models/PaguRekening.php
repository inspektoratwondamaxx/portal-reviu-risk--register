<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaguRekening extends Model
{
    protected $table = 'pagu_rekening';

    protected $fillable = [
        'kegiatan_id',
        'kode_rekening_id',
        'pagu_anggaran',
    ];

    protected function casts(): array
    {
        return [
            'pagu_anggaran' => 'decimal:2',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function kodeRekening(): BelongsTo
    {
        return $this->belongsTo(KodeRekening::class);
    }
}
