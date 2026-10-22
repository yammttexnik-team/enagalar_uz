<?php

namespace App\Models;

use App\Enums\BonusType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'amount', 'type', 'description', 'campaign_id', 'reference', 'expires_at', 'created_by'])]
class BonusTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => BonusType::class,
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
