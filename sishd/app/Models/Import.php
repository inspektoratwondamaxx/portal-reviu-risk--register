<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $fillable = [
        'jenis', 'file_path', 'file_name', 'total_baris', 'sukses', 'gagal', 'status', 'error_log', 'user_id',
    ];

    protected function casts(): array
    {
        return ['error_log' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
