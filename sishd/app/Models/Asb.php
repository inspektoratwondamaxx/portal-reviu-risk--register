<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asb extends Model
{
    use LogsAudit;

    protected $table = 'asb';

    protected $fillable = [
        'kode', 'nama_kegiatan', 'kelompok_kegiatan', 'satuan_variabel', 'batas_minimal', 'batas_maksimal',
        'hasil_perhitungan', 'tahun_anggaran_id', 'status', 'is_active', 'opd_id', 'catatan',
        'last_calculated_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'batas_minimal' => 'decimal:2',
            'batas_maksimal' => 'decimal:2',
            'hasil_perhitungan' => 'decimal:2',
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

    public function variables(): HasMany
    {
        return $this->hasMany(AsbVariable::class)->orderBy('urutan');
    }

    public function formula(): HasOne
    {
        return $this->hasOne(AsbFormula::class);
    }

    /** Menilai kewajaran (Bab 9 kajian: "analisis standar belanja untuk menilai kewajaran biaya"). */
    public function isWajar(): ?bool
    {
        if ($this->batas_minimal === null && $this->batas_maksimal === null) {
            return null;
        }

        $hasil = (float) $this->hasil_perhitungan;

        if ($this->batas_minimal !== null && $hasil < (float) $this->batas_minimal) {
            return false;
        }

        if ($this->batas_maksimal !== null && $hasil > (float) $this->batas_maksimal) {
            return false;
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', ItemStatus::Aktif->value);
    }
}
