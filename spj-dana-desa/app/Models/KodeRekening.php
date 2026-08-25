<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeRekening extends Model
{
    protected $table = 'kode_rekening';

    public const JENIS_PEGAWAI = 'pegawai';

    public const JENIS_BARANG_JASA = 'barang_jasa';

    public const JENIS_MODAL = 'modal';

    public const JENIS_TAK_TERDUGA = 'tak_terduga';

    protected $fillable = [
        'kode',
        'uraian',
        'jenis_belanja',
        'tahun_anggaran',
    ];

    public function paguRekening(): HasMany
    {
        return $this->hasMany(PaguRekening::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }
}
