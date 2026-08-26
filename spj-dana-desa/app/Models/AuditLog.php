<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tabel append-only — tidak ada penghapusan permanen dari antarmuka aplikasi (Bab IV.4). */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'kampung_id',
        'model_type',
        'model_id',
        'action',
        'data_sebelum',
        'data_sesudah',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $log) => $log->created_at ??= now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }
}
