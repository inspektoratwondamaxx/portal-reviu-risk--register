<?php

namespace App\Models;

use App\Support\ItemTypeResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalItem extends Model
{
    protected $fillable = [
        'proposal_id', 'item_type', 'existing_item_id', 'data_usulan', 'kemiripan', 'created_item_id',
    ];

    protected function casts(): array
    {
        return [
            'data_usulan' => 'array',
            'kemiripan' => 'array',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function existingItem(): ?Model
    {
        return $this->existing_item_id ? ItemTypeResolver::resolve($this->item_type, $this->existing_item_id) : null;
    }

    public function createdItem(): ?Model
    {
        return $this->created_item_id ? ItemTypeResolver::resolve($this->item_type, $this->created_item_id) : null;
    }

    public function hasSimilarWarning(): bool
    {
        return ! empty($this->kemiripan);
    }
}
