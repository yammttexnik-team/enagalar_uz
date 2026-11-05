<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SEO qo'llanma maqolasi (uz + ru, markdown). Claude yozadi, admin tuzatishi mumkin. */
#[Fillable([
    'slug', 'topic_key', 'title_uz', 'title_ru', 'excerpt_uz', 'excerpt_ru', 'body_uz', 'body_ru',
    'region_id', 'status', 'published_at',
])]
class Article extends Model
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)->where('published_at', '<=', now());
    }
}
