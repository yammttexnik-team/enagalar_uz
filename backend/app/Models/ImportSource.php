<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** E'lon olinadigan ochiq Telegram kanal (admin paneldan boshqariladi). */
#[Fillable(['channel', 'title', 'is_active', 'last_post_id', 'last_fetched_at', 'notes'])]
class ImportSource extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(ImportedListing::class);
    }

    public function url(): string
    {
        return 'https://t.me/'.$this->channel;
    }
}
