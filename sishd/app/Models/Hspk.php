<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hspk extends Model
{
    use LogsAudit;

    protected $table = 'hspk';

    protected $fillable = [
        'kode', 'uraian', 'jenis_pekerjaan', 'satuan', 'tahun_anggaran_id', 'harga_satuan',
        'status', 'is_active', 'opd_id', 'catatan', 'last_calculated_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'status' => ItemStatus::class,
            'is_active' => 'boolean',
            'last_calculated_at' => 'datetime',
        ];
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(HspkComponent::class)->orderBy('urutan');
    }

    public function analysis(): HasMany
    {
        return $this->hasMany(HspkAnalysis::class)->latest('dihitung_pada');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', ItemStatus::Aktif->value);
    }

    public function componentsByType(string $type)
    {
        return $this->components->where('komponen_type', $type);
    }
}
