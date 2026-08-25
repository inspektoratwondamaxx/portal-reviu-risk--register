<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kampung extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode_kampung',
        'nama_kampung',
        'kecamatan',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function periodeSpj(): HasMany
    {
        return $this->hasMany(PeriodeSpj::class);
    }

    public function pendampingWilayah(): HasMany
    {
        return $this->hasMany(PendampingWilayah::class);
    }
}
