<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCode extends Model
{
    protected $fillable = ['asset_group_id', 'kode', 'nama', 'keterangan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assetGroup(): BelongsTo
    {
        return $this->belongsTo(AssetGroup::class);
    }

    public function codeMappings(): HasMany
    {
        return $this->hasMany(CodeMapping::class);
    }

    public function sshItems(): HasMany
    {
        return $this->hasMany(SshItem::class);
    }
}
