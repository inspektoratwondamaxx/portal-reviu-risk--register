<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceSurvey extends Model
{
    protected $fillable = [
        'ssh_item_id', 'uraian_barang', 'spesifikasi', 'merek', 'vendor_id', 'lokasi',
        'tanggal_survei', 'harga', 'surveyor_id', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_survei' => 'date',
            'harga' => 'decimal:2',
        ];
    }

    public function sshItem(): BelongsTo
    {
        return $this->belongsTo(SshItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(PriceEvidence::class);
    }
}
