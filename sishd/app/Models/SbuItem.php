<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Models\Concerns\HasPriceHistory;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SbuItem extends Model
{
    use HasPriceHistory, LogsAudit;

    public const KATEGORI = [
        'honorarium' => 'Honorarium',
        'perjalanan_dinas' => 'Perjalanan Dinas',
        'konsumsi' => 'Konsumsi',
        'transportasi' => 'Transportasi',
        'akomodasi' => 'Akomodasi',
        'lainnya' => 'Biaya Umum Lainnya',
    ];

    protected $fillable = [
        'kode', 'kategori', 'uraian', 'satuan', 'wilayah', 'besaran', 'tahun_anggaran_id',
        'dasar_penetapan', 'keterangan', 'opd_id', 'status', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'besaran' => 'decimal:2',
            'status' => ItemStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function priceColumn(): string
    {
        return 'besaran';
    }

    public function priceItemType(): string
    {
        return 'sbu';
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function hspkComponents(): HasMany
    {
        return $this->hasMany(HspkComponent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', ItemStatus::Aktif->value);
    }
}
