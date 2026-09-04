<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalReview extends Model
{
    public const TAHAPAN = [
        'verifikator' => 'Verifikator',
        'tim_standar_harga' => 'Tim Standar Harga',
        'pejabat_berwenang' => 'Pejabat Berwenang',
    ];

    protected $fillable = ['proposal_id', 'reviewer_id', 'tahapan', 'keputusan', 'catatan', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
