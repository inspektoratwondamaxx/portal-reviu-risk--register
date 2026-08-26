<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKampung;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kegiatan extends Model
{
    use BelongsToKampung, HasFactory, LogsAudit, SoftDeletes;

    protected $table = 'kegiatan';

    protected $fillable = [
        'kampung_id',
        'bidang_kegiatan_id',
        'nama_kegiatan',
        'tahun_anggaran',
        'pagu_total',
    ];

    protected function casts(): array
    {
        return [
            'pagu_total' => 'decimal:2',
        ];
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }

    public function bidangKegiatan(): BelongsTo
    {
        return $this->belongsTo(BidangKegiatan::class);
    }

    public function paguRekening(): HasMany
    {
        return $this->hasMany(PaguRekening::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    /** Sisa pagu per kode rekening tertentu — dasar KF-08 (deteksi kewajaran anggaran). */
    public function sisaPagu(int $kodeRekeningId): string
    {
        $pagu = $this->paguRekening()->where('kode_rekening_id', $kodeRekeningId)->value('pagu_anggaran') ?? 0;

        $terpakai = $this->transaksis()
            ->where('kode_rekening_id', $kodeRekeningId)
            ->whereNotIn('status', ['revisi'])
            ->sum('nominal');

        return number_format((float) $pagu - (float) $terpakai, 2, '.', '');
    }
}
