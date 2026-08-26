<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    public const TIPE_TRANSAKSI_SINKRON = 'transaksi_disinkronkan';

    public const TIPE_VERIFIKASI_OCR = 'verifikasi_ocr';

    public const TIPE_ANOMALI = 'anomali';

    public const TIPE_STATUS_SPJ = 'status_spj';

    public const TIPE_CATATAN_BARU = 'catatan_baru';

    protected $fillable = [
        'user_id',
        'tipe',
        'judul',
        'pesan',
        'data',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'dibaca_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tandaiDibaca(): void
    {
        $this->dibaca_at ??= now();
        $this->save();
    }
}
