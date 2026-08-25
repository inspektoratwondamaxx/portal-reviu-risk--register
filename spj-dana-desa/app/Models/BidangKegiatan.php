<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidangKegiatan extends Model
{
    protected $table = 'bidang_kegiatan';

    protected $fillable = [
        'kode',
        'nama_bidang',
        'tahun_anggaran',
    ];

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }
}
