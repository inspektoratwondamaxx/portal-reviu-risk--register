<?php

namespace App\Models;

use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    use LogsAudit;

    protected $fillable = [
        'kode', 'nama', 'singkatan', 'alamat', 'telepon', 'email', 'kepala_opd', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
