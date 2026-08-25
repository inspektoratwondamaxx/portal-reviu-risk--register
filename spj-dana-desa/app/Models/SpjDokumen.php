<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SpjDokumen extends Model
{
    protected $table = 'spj_dokumen';

    protected $fillable = [
        'periode_spj_id',
        'path_pdf',
        'versi',
        'generated_by',
    ];

    public function periodeSpj(): BelongsTo
    {
        return $this->belongsTo(PeriodeSpj::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function urlPdf(): string
    {
        return Storage::disk('bukti')->url($this->path_pdf);
    }
}
