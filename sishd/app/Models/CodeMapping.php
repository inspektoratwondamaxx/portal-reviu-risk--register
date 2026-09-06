<?php

namespace App\Models;

use App\Enums\MappingStatus;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeMapping extends Model
{
    use LogsAudit;

    protected $fillable = [
        'asset_code_id', 'account_code_id', 'sipd_code_id', 'status', 'catatan', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MappingStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    public function assetCode(): BelongsTo
    {
        return $this->belongsTo(AssetCode::class);
    }

    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function sipdCode(): BelongsTo
    {
        return $this->belongsTo(SipdCode::class);
    }
}
