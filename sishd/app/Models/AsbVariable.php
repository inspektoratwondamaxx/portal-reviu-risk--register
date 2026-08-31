<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsbVariable extends Model
{
    public const SUMBER = [
        'manual' => 'Input Manual',
        'ssh_item' => 'Master SSH',
        'sbu_item' => 'Master SBU',
        'hspk' => 'HSPK',
    ];

    protected $fillable = [
        'asb_id', 'kode_variabel', 'label', 'nilai', 'satuan', 'sumber_tipe', 'sumber_id', 'urutan',
    ];

    protected function casts(): array
    {
        return ['nilai' => 'decimal:4'];
    }

    public function asb(): BelongsTo
    {
        return $this->belongsTo(Asb::class);
    }

    public function sumber(): ?Model
    {
        return match ($this->sumber_tipe) {
            'ssh_item' => SshItem::find($this->sumber_id),
            'sbu_item' => SbuItem::find($this->sumber_id),
            'hspk' => Hspk::find($this->sumber_id),
            default => null,
        };
    }

    public function isDinamis(): bool
    {
        return $this->sumber_tipe !== 'manual' && $this->sumber_id !== null;
    }
}
