<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsbFormula extends Model
{
    protected $fillable = ['asb_id', 'ekspresi', 'keterangan', 'created_by'];

    public function asb(): BelongsTo
    {
        return $this->belongsTo(Asb::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
