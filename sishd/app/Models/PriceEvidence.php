<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceEvidence extends Model
{
    protected $table = 'price_evidence';

    public const JENIS = [
        'foto_toko' => 'Foto Toko',
        'foto_daftar_harga' => 'Foto Daftar Harga',
        'dokumen_penawaran' => 'Dokumen Penawaran',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['price_survey_id', 'file_path', 'jenis_bukti', 'keterangan'];

    public function priceSurvey(): BelongsTo
    {
        return $this->belongsTo(PriceSurvey::class);
    }

    public function url(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->file_path);
    }
}
