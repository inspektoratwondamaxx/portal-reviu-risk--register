<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SuratKeluar extends Model
{
    protected $table = 'surat_keluar';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal_surat' => 'date'];
    }

    public function dokumen(): MorphMany
    {
        return $this->morphMany(Dokumen::class, 'dokumenable');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if (blank($kata)) {
            return $query;
        }
        $like = '%'.mb_strtolower(trim($kata)).'%';

        return $query->where(function (Builder $q) use ($like) {
            foreach (['nomor_agenda', 'nomor_surat', 'tujuan', 'perihal', 'ringkasan', 'klasifikasi'] as $kolom) {
                $q->orWhereRaw("LOWER($kolom) LIKE ?", [$like]);
            }
            $q->orWhereHas('dokumen', fn ($d) => $d->whereRaw('LOWER(teks_ocr) LIKE ?', [$like])
                ->orWhereRaw('LOWER(nama_asli) LIKE ?', [$like]));
        });
    }
}
