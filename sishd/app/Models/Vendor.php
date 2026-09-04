<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'kode', 'nama', 'alamat', 'kecamatan', 'kelurahan', 'telepon', 'kontak_person', 'npwp', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function priceSurveys(): HasMany
    {
        return $this->hasMany(PriceSurvey::class);
    }
}
