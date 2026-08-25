<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAiLog extends Model
{
    public $timestamps = false;

    protected $table = 'chat_ai_logs';

    protected $fillable = [
        'user_id',
        'kampung_id',
        'pertanyaan',
        'jawaban',
    ];

    protected function casts(): array
    {
        return [
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
}
