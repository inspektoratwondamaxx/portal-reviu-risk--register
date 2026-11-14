<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SuratMasuk extends Model
{
    protected $table = 'surat_masuk';

    protected $guarded = [];

    public const SIFAT = ['biasa', 'penting', 'segera', 'rahasia'];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_terima' => 'date',
        ];
    }

    public function dokumen(): MorphMany
    {
        return $this->morphMany(Dokumen::class, 'dokumenable');
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Staf hanya melihat surat yang didisposisikan kepadanya. */
    public function scopeDapatDilihat(Builder $query, User $user): Builder
    {
        if ($user->bolehLihatSemua()) {
            return $query;
        }

        return $query->whereHas('disposisi', fn ($q) => $q->where('kepada_user_id', $user->id));
    }

    /** Pencarian teks pada kolom register dan hasil OCR dokumen. */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if (blank($kata)) {
            return $query;
        }
        $like = '%'.mb_strtolower(trim($kata)).'%';

        return $query->where(function (Builder $q) use ($like) {
            foreach (['nomor_agenda', 'nomor_surat', 'asal_surat', 'perihal', 'ringkasan', 'klasifikasi'] as $kolom) {
                $q->orWhereRaw("LOWER($kolom) LIKE ?", [$like]);
            }
            $q->orWhereHas('dokumen', fn ($d) => $d->whereRaw('LOWER(teks_ocr) LIKE ?', [$like])
                ->orWhereRaw('LOWER(nama_asli) LIKE ?', [$like]));
        });
    }
}
