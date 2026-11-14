<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposisi extends Model
{
    protected $table = 'disposisi';

    protected $guarded = [];

    public const INSTRUKSI = [
        'Untuk diketahui',
        'Tindak lanjuti',
        'Koordinasikan',
        'Siapkan jawaban/konsep surat',
        'Hadiri/wakili',
        'Pelajari dan laporkan',
        'Arsipkan',
    ];

    protected function casts(): array
    {
        return [
            'instruksi' => 'array',
            'batas_waktu' => 'date',
            'selesai_at' => 'datetime',
        ];
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function dari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }
}
