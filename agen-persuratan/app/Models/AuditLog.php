<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['detail' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(string $aksi, ?Model $objek = null, array $detail = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'objek' => $objek ? class_basename($objek) : null,
            'objek_id' => $objek?->getKey(),
            'detail' => $detail ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
