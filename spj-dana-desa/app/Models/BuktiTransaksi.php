<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BuktiTransaksi extends Model
{
    use SoftDeletes;

    protected $table = 'bukti_transaksi';

    public const OCR_BELUM_DIPROSES = 'belum_diproses';

    public const OCR_DIPROSES = 'diproses';

    public const OCR_SELESAI = 'selesai';

    public const OCR_GAGAL = 'gagal';

    protected $fillable = [
        'transaksi_id',
        'path_file',
        'latitude',
        'longitude',
        'diambil_at',
        'hasil_ocr_raw',
        'status_ocr',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'diambil_at' => 'datetime',
            'hasil_ocr_raw' => 'array',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function urlFile(): string
    {
        return Storage::disk('bukti')->url($this->path_file);
    }
}
