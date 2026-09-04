<?php

namespace App\Models;

use App\Enums\KomponenType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HspkComponent extends Model
{
    protected $fillable = [
        'hspk_id', 'komponen_type', 'ssh_item_id', 'sbu_item_id', 'uraian',
        'koefisien', 'satuan', 'harga_satuan', 'subtotal', 'urutan',
    ];

    protected function casts(): array
    {
        return [
            'komponen_type' => KomponenType::class,
            'koefisien' => 'decimal:4',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function hspk(): BelongsTo
    {
        return $this->belongsTo(Hspk::class);
    }

    public function sshItem(): BelongsTo
    {
        return $this->belongsTo(SshItem::class);
    }

    public function sbuItem(): BelongsTo
    {
        return $this->belongsTo(SbuItem::class);
    }

    public function label(): string
    {
        return $this->uraian ?: ($this->sshItem?->uraian ?? $this->sbuItem?->uraian ?? '-');
    }
}
