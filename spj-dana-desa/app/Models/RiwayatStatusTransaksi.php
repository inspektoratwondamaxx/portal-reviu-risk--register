<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusTransaksi extends Model
{
    public $timestamps = false;

    protected $table = 'riwayat_status_transaksi';

    protected $fillable = [
        'transaksi_id',
        'status_lama',
        'status_baru',
        'catatan',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
