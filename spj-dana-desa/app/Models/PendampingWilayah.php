<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendampingWilayah extends Model
{
    protected $table = 'pendamping_wilayah';

    protected $fillable = [
        'user_id',
        'kampung_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kampung(): BelongsTo
    {
        return $this->belongsTo(Kampung::class);
    }
}
