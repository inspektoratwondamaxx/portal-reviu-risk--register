<?php

namespace App\Models;

use App\Support\ItemTypeResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $fillable = [
        'item_type', 'item_id', 'harga_lama', 'harga_baru', 'persentase',
        'dasar_perubahan', 'keterangan', 'user_id', 'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'harga_lama' => 'decimal:2',
            'harga_baru' => 'decimal:2',
            'persentase' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): ?Model
    {
        return ItemTypeResolver::resolve($this->item_type, $this->item_id);
    }

    public function itemLabel(): string
    {
        return ItemTypeResolver::labels()[$this->item_type] ?? strtoupper($this->item_type);
    }
}
