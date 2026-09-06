<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HspkAnalysis extends Model
{
    protected $table = 'hspk_analysis';

    public $timestamps = false;

    protected $fillable = [
        'hspk_id', 'harga_sebelum', 'harga_sesudah', 'selisih', 'persentase',
        'pemicu', 'pemicu_type', 'pemicu_id', 'dihitung_oleh', 'dihitung_pada',
    ];

    protected function casts(): array
    {
        return [
            'harga_sebelum' => 'decimal:2',
            'harga_sesudah' => 'decimal:2',
            'selisih' => 'decimal:2',
            'persentase' => 'decimal:2',
            'dihitung_pada' => 'datetime',
        ];
    }

    public function hspk(): BelongsTo
    {
        return $this->belongsTo(Hspk::class);
    }

    public function dihitungOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dihitung_oleh');
    }
}
