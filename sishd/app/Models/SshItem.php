<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Models\Concerns\HasPriceHistory;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SshItem extends Model
{
    use HasPriceHistory, LogsAudit;

    protected $fillable = [
        'kode_barang', 'asset_code_id', 'account_code_id', 'category_id', 'tahun_anggaran_id',
        'uraian', 'spesifikasi', 'merek', 'satuan', 'harga', 'sumber_harga', 'keterangan',
        'opd_id', 'status', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'status' => ItemStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function priceColumn(): string
    {
        return 'harga';
    }

    public function priceItemType(): string
    {
        return 'ssh';
    }

    public function assetCode(): BelongsTo
    {
        return $this->belongsTo(AssetCode::class);
    }

    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hspkComponents(): HasMany
    {
        return $this->hasMany(HspkComponent::class);
    }

    public function priceSurveys(): HasMany
    {
        return $this->hasMany(PriceSurvey::class);
    }

    public function priceHistories()
    {
        return PriceHistory::query()->where('item_type', 'ssh')->where('item_id', $this->id);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', ItemStatus::Aktif->value);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('uraian', 'ilike', "%{$term}%")
                ->orWhere('kode_barang', 'ilike', "%{$term}%")
                ->orWhere('merek', 'ilike', "%{$term}%");
        });
    }
}
