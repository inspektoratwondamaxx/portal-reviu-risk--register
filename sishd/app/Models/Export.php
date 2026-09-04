<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    protected $fillable = ['jenis', 'format', 'filter', 'file_path', 'total_baris', 'user_id'];

    protected function casts(): array
    {
        return ['filter' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
