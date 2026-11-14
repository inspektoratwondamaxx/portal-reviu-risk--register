<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Dokumen extends Model
{
    protected $table = 'dokumen';

    protected $guarded = [];

    protected $hidden = ['teks_ocr'];

    public function dokumenable(): MorphTo
    {
        return $this->morphTo();
    }
}
