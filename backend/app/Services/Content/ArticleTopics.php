<?php

namespace App\Services\Content;

use App\Models\Article;
use App\Models\Region;

/** Maqola mavzulari rejasi: deterministik ro'yxat, keyingi mavzu = hali maqolasi yo'q birinchi kalit. */
class ArticleTopics
{
    /** Umumiy qo'llanmalar: kalit => mavzu tavsifi (Claude'ga beriladi). */
    public const GENERAL = [
        'choose-nanny' => 'How to choose a nanny for your child: what to check, red flags, where to look',
        'interview-questions' => 'Interview questions parents should ask a nanny, and what good answers look like',
        'contract-tips' => 'Nanny agreement/contract tips: duties, schedule, pay, days off, notice period, what to write down',
        'safety-rules' => 'Safety rules when hiring a nanny: never pay in advance, trial day, check references, documents, how to stay safe',
        'work-formats' => 'Hourly vs full-time vs live-in nanny: differences, pros and cons, which family needs which',
        'become-nanny' => 'How to become a nanny and what parents look for: skills, experience, how to write a good profile',
        'infant-nanny' => 'Choosing a nanny for an infant (0-1 year): special requirements, feeding, sleep, safety, experience',
        'first-day' => 'First day with a new nanny checklist: what to prepare, what to explain, how to start',
        'nanny-vs-kindergarten' => 'Nanny or kindergarten: how to decide, cost, age, child development, combining both',
        'write-vacancy' => 'How to write a good nanny vacancy: what to include (schedule, pay, children ages), how to get good replies',
    ];

    /** @return list<string> */
    public function keys(): array
    {
        $keys = array_keys(self::GENERAL);

        foreach (Region::query()->orderBy('sort')->orderBy('id')->pluck('slug') as $slug) {
            $keys[] = 'price-'.$slug;
        }

        return $keys;
    }

    public function next(): ?string
    {
        $done = Article::query()->whereNotNull('topic_key')->pluck('topic_key')->all();

        foreach ($this->keys() as $key) {
            if (! in_array($key, $done, true)) {
                return $key;
            }
        }

        return null;
    }

    public function regionSlug(string $key): ?string
    {
        return str_starts_with($key, 'price-') ? substr($key, 6) : null;
    }
}
