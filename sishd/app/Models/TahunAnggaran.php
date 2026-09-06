<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAnggaran extends Model
{
    protected $fillable = [
        'tahun', 'status', 'tanggal_mulai', 'tanggal_selesai', 'is_active', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public static function aktif(): ?self
    {
        return static::where('is_active', true)->first() ?? static::orderByDesc('tahun')->first();
    }
}
