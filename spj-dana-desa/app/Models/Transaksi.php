<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKampung;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Entitas inti transaksi belanja (Bab V.2). Alur status mengikuti Bab IV.2:
 * draft -> terverifikasi -> diajukan -> disetujui_pendamping -> disetujui_inspektorat -> final,
 * dengan cabang "revisi" saat ditolak pada tahap manapun.
 */
class Transaksi extends Model
{
    use BelongsToKampung, HasFactory, LogsAudit, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TERVERIFIKASI = 'terverifikasi';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI_PENDAMPING = 'disetujui_pendamping';

    public const STATUS_DISETUJUI_INSPEKTORAT = 'disetujui_inspektorat';

    public const STATUS_REVISI = 'revisi';

    public const STATUS_FINAL = 'final';

    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_OCR_AI = 'ocr_ai';

    protected $fillable = [
        'uuid',
        'kampung_id',
        'kegiatan_id',
        'kode_rekening_id',
        'tanggal_transaksi',
        'uraian',
        'nominal',
        'status',
        'sumber_input',
        'is_flagged',
        'catatan_flag',
        'dibuat_offline',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'nominal' => 'decimal:2',
            'is_flagged' => 'boolean',
            'dibuat_offline' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaksi) {
            $transaksi->uuid ??= (string) Str::uuid();
            $transaksi->status ??= self::STATUS_DRAFT;
            $transaksi->created_by ??= Auth::id();
        });

        static::updating(function (self $transaksi) {
            $transaksi->updated_by = Auth::id();
        });
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function kodeRekening(): BelongsTo
    {
        return $this->belongsTo(KodeRekening::class);
    }

    public function buktiTransaksi(): HasMany
    {
        return $this->hasMany(BuktiTransaksi::class);
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusTransaksi::class)->orderByDesc('changed_at');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Mencatat perpindahan status ke riwayat_status_transaksi (Bab IV.4 / KF-15). */
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
}
