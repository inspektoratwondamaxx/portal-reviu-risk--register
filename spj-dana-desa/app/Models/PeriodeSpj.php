<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKampung;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/** Periode pelaporan SPJ bulanan (Bab V.2), pembungkus BKU + dokumen SPJ final. */
class PeriodeSpj extends Model
{
    use BelongsToKampung, HasFactory, LogsAudit, SoftDeletes;

    protected $table = 'periode_spj';

    public const STATUS_PROSES = 'proses';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI_PENDAMPING = 'disetujui_pendamping';

    public const STATUS_DISETUJUI_INSPEKTORAT = 'disetujui_inspektorat';

    public const STATUS_REVISI = 'revisi';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'kampung_id',
        'tahun_anggaran',
        'bulan',
        'status',
        'saldo_akhir',
    ];

    protected function casts(): array
    {
        return [
            'saldo_akhir' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $periode) => $periode->status ??= self::STATUS_PROSES);
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }

    public function transaksis(): BelongsToMany
    {
        return $this->belongsToMany(Transaksi::class, 'periode_spj_transaksi')
            ->withPivot('urutan_bku')
            ->orderByPivot('urutan_bku');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusSpj::class)->orderByDesc('changed_at');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(SpjDokumen::class);
    }

    public function dokumenTerbaru(): ?SpjDokumen
    {
        return $this->dokumen()->latest('versi')->first();
    }

    public function ubahStatus(string $statusBaru, ?string $catatan = null): void
    {
        $statusLama = $this->status;

        $this->update(['status' => $statusBaru]);

        $this->riwayatStatus()->create([
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
            'catatan' => $catatan,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);
    }

    /** Menghitung ulang saldo_akhir dari total nominal transaksi final periode ini. */
    public function hitungSaldoAkhir(): string
    {
        return (string) $this->transaksis()->sum('nominal');
    }
}
