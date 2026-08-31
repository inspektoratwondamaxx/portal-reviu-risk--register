<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Enums\RoleName;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use LogsAudit;

    /** Urutan approval berjenjang (Bab 22.3 kajian): Verifikator -> Tim Standar Harga -> Pejabat Berwenang -> aktif. */
    public const TAHAPAN_URUTAN = ['verifikator', 'tim_standar_harga', 'pejabat_berwenang'];

    protected $fillable = [
        'nomor_usulan', 'opd_id', 'jenis_usulan', 'tipe_perubahan', 'status', 'tahapan_saat_ini',
        'alasan_usulan', 'catatan_verifikasi', 'verifikator_id', 'diajukan_at', 'verified_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'diajukan_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProposalReview::class)->orderBy('reviewed_at');
    }

    public static function generateNomor(string $jenisUsulan): string
    {
        $tahun = now()->year;
        $prefix = "USL-{$tahun}-";
        $last = static::where('nomor_usulan', 'like', "{$prefix}%")->orderByDesc('nomor_usulan')->first();
        $urut = $last ? ((int) substr($last->nomor_usulan, -5)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 5, '0', STR_PAD_LEFT);
    }

    /** Tahap berikutnya dalam rantai approval, atau null jika $tahapan sudah yang terakhir. */
    public static function nextTahapan(string $tahapan): ?string
    {
        $idx = array_search($tahapan, self::TAHAPAN_URUTAN, true);

        return $idx !== false && isset(self::TAHAPAN_URUTAN[$idx + 1]) ? self::TAHAPAN_URUTAN[$idx + 1] : null;
    }

    public static function roleForTahapan(string $tahapan): RoleName
    {
        return match ($tahapan) {
            'tim_standar_harga' => RoleName::TimStandarHarga,
            'pejabat_berwenang' => RoleName::PejabatBerwenang,
            default => RoleName::Verifikator,
        };
    }

    public function tahapanKe(): int
    {
        $idx = array_search($this->tahapan_saat_ini, self::TAHAPAN_URUTAN, true);

        return $idx !== false ? $idx + 1 : 1;
    }
}
