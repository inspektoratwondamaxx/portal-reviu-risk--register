<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetGroup extends Model
{
    protected $fillable = ['kode', 'nama', 'keterangan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assetCodes(): HasMany
    {
        return $this->hasMany(AssetCode::class);
    }
}
