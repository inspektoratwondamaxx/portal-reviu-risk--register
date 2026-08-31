<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use LogsAudit;

    protected $fillable = [
        'nomor_usulan', 'opd_id', 'jenis_usulan', 'tipe_perubahan', 'status', 'alasan_usulan',
        'catatan_verifikasi', 'verifikator_id', 'diajukan_at', 'verified_at', 'created_by',
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
}
