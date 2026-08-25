<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeSpjTransaksi extends Model
{
    protected $table = 'periode_spj_transaksi';

    protected $fillable = [
        'periode_spj_id',
        'transaksi_id',
        'urutan_bku',
    ];
}
