<?php

namespace App\Services\Content;

use Anthropic\Client;
use App\Enums\ImportedListingStatus;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\Vacancy;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Bitta Claude so'rovi bilan ikki tilda (uz lotin + ru) SEO qo'llanma yozadi va e'lon qiladi.
 * Narx mavzularida Claude'ga faqat bazadagi HAQIQIY statistika beriladi.
 */
class ArticleWriter
{
    public const FEATURE = 'article';

    /** Statistikaga kiradigan guruhda kamida shuncha e'lon bo'lishi kerak. */
    public const MIN_GROUP = 3;

    private const SYSTEM_PROMPT = <<<'TXT'
You are an editor of enagalar.uz, an Uzbekistan marketplace connecting parents and nannies (enaga). You write practical SEO guides for parents and nannies in Uzbekistan.

You receive JSON: "topic" (what to write), optional "region" (names in uz/ru), "year", and optional "stats" (REAL numbers from our database).

Write the SAME article in two languages:
- "uz": Uzbek in Latin script (use oʻ and gʻ with the ʻ character, e.g. oʻzbek, goʻdak, togʻri).
- "ru": natural Russian.

Rules:
- 600-900 words per language body. Practical, concrete, friendly; written for real parents/nannies in Uzbekistan (local context: mahalla, Tashkent districts, so'm, family customs, common work formats).
- NEVER invent statistics, percentages, laws, surveys, organisations or quotes. Use numbers ONLY from "stats". If "stats" is absent or "enough_data" is false, do not state exact prices; speak cautiously ("taxminan", "usually depends on...") and explain what affects the price (hours, children's age, experience, district, live-in).
- When stats are used, say they are based on listings on enagalar.uz and other open sources in recent weeks, and that real prices vary. Amounts in so'm, with spaces as thousand separators (3 000 000 so'm).
- Do not give legal or medical advice as certain facts; keep it general and suggest checking details.
- Markdown body: use "## " subheadings (no H1, the title is shown separately), short paragraphs, "- " bullet lists, **bold** for key points. No HTML, no tables, no images, no emoji.
- "title": at most 70 characters, natural search-friendly headline; include the year when the topic is price related.
- "excerpt": 1-2 sentences, at most 200 characters, a meta description.
- The article must end with a short soft call to action to enagalar.uz: parents can post a vacancy or search nannies, nannies can post a profile, it is free to start. Mention the site name "enagalar.uz" as plain text (no links needed).
TXT;

    public function __construct(private readonly ArticleTopics $topics) {}

    /**
     * @throws RuntimeException
     */
    public function write(string $topicKey): Article
    {
        if ($existing = Article::where('topic_key', $topicKey)->first()) {
            return $existing;
        }

        $input = $this->input($topicKey);
        $model = (string) config('services.anthropic.content_model');
        $inputTokens = $outputTokens = null;
        $status = 'error';

        try {
            $response = $this->callApi(
                self::SYSTEM_PROMPT,
                json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $model,
                $this->schema(),
            );
            $inputTokens = $response['input_tokens'];
            $outputTokens = $response['output_tokens'];
            $status = match ($response['stop_reason']) {
                'refusal' => 'refused',
                'max_tokens' => 'truncated',
                default => 'ok',
            };
            if ($status !== 'ok') {
                throw new RuntimeException("AI javobi to'liq emas ($status)");
            }

            $data = json_decode((string) $response['text'], true);
            if (! is_array($data)) {
                $status = 'invalid';
                throw new RuntimeException('AI javobi JSON emas');
            }
            $this->validate($data);
        } catch (Throwable $e) {
            $status = $status === 'ok' ? 'invalid' : $status;
            throw $e instanceof RuntimeException ? $e : new RuntimeException($e->getMessage(), 0, $e);
        } finally {
            AiRequest::create([
                'user_id' => null,
                'feature' => self::FEATURE,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'status' => $status,
            ]);
        }

        return Article::create([
            'slug' => $this->uniqueSlug($data['title_uz']),
            'topic_key' => $topicKey,
            'title_uz' => trim($data['title_uz']),
            'title_ru' => trim($data['title_ru']),
            'excerpt_uz' => trim($data['excerpt_uz']),
            'excerpt_ru' => trim($data['excerpt_ru']),
            'body_uz' => trim($data['body_uz']),
            'body_ru' => trim($data['body_ru']),
            'region_id' => $input['region']['id'] ?? null,
            'status' => Article::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Claude'ga yuboriladigan kirish (testlarda ham tekshiriladi).
     *
     * @return array<string, mixed>
     */
    public function input(string $topicKey): array
    {
        $input = ['year' => (int) now('Asia/Tashkent')->format('Y')];

        if ($slug = $this->topics->regionSlug($topicKey)) {
            $region = Region::where('slug', $slug)->firstOrFail();
            $input['topic'] = "Nanny prices in {$region->name_uz} ({$region->name_ru}), Uzbekistan, {$input['year']}: what parents pay, what nannies ask, what affects the price, tips to agree on a fair price. Title form: \"{Region} enaga narxlari {year}\".";
            $input['region'] = ['id' => $region->id, 'uz' => $region->name_uz, 'ru' => $region->name_ru];
            $input['stats'] = $this->priceStats($region);
        } elseif (isset(ArticleTopics::GENERAL[$topicKey])) {
            $input['topic'] = ArticleTopics::GENERAL[$topicKey];
        } else {
            throw new RuntimeException("Noma'lum mavzu: $topicKey");
        }

        return $input;
    }

    /**
     * Viloyat bo'yicha haqiqiy narx statistikasi: Telegramdan olingan e'lonlar (faol + oxirgi 60 kundagi arxiv),
     * saytdagi faol vakansiya va anketalar. Guruh = tomon (vacancy|nanny) x narx birligi x ish formati.
     *
     * @return array<string, mixed>
     */
    public function priceStats(Region $region): array
    {
        $rows = []; // [side, unit, format, amount]

        $imported = ImportedListing::query()
            ->where('region_id', $region->id)
            ->where(fn ($q) => $q
                ->where('status', ImportedListingStatus::Active)
                ->orWhere(fn ($q) => $q->where('status', ImportedListingStatus::Archived)->where('archived_at', '>=', now()->subDays(60))))
            ->get(['type', 'price_min', 'price_max', 'price_unit', 'work_format']);
        foreach ($imported as $l) {
            $amount = $l->price_min && $l->price_max ? (int) round(($l->price_min + $l->price_max) / 2) : ($l->price_min ?: $l->price_max);
            $rows[] = [$l->type, $l->price_unit?->value, $l->work_format?->value, $amount ?: null];
        }

        $vacancies = Vacancy::query()->active()->where('region_id', $region->id)->get(['salary_min', 'salary_max', 'salary_unit', 'work_format']);
        foreach ($vacancies as $v) {
            $amount = $v->salary_min && $v->salary_max ? (int) round(($v->salary_min + $v->salary_max) / 2) : ($v->salary_min ?: $v->salary_max);
            $rows[] = ['vacancy', $v->salary_unit?->value, $v->work_format?->value, $amount ?: null];
        }

        $nannies = NannyProfile::query()->active()->where('region_id', $region->id)->get(['price_amount', 'price_unit', 'work_formats']);
        foreach ($nannies as $n) {
            foreach ($n->work_formats ?: [null] as $format) {
                $rows[] = ['nanny', $n->price_unit?->value, $format, $n->price_amount ?: null];
            }
        }

        $groups = [];
        foreach ($rows as [$side, $unit, $format, $amount]) {
            if (! $amount || ! $unit || ! $format) {
                continue;
            }
            $groups["$side|$unit|$format"][] = (int) $amount;
        }

        $out = [];
        foreach ($groups as $key => $amounts) {
            if (count($amounts) < self::MIN_GROUP) {
                continue;
            }
            [$side, $unit, $format] = explode('|', $key);
            sort($amounts);
            $out[] = [
                'side' => $side === 'vacancy' ? 'parents_offer' : 'nannies_ask',
                'price_unit' => $unit,
                'work_format' => $format,
                'count' => count($amounts),
                'median' => (int) round($this->median($amounts)),
                'min' => $amounts[0],
                'max' => end($amounts),
            ];
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [
            'currency' => 'UZS',
            'period' => 'recent weeks (about last 60 days)',
            'listings_total' => count($rows),
            'enough_data' => $out !== [],
            'groups' => $out,
        ];
    }

    /** @param  list<int>  $sorted */
    private function median(array $sorted): float
    {
        $n = count($sorted);
        $mid = intdiv($n, 2);

        return $n % 2 ? $sorted[$mid] : ($sorted[$mid - 1] + $sorted[$mid]) / 2;
    }

    /** @param  array<string, mixed>  $data */
    private function validate(array $data): void
    {
        foreach (['title', 'excerpt', 'body'] as $field) {
            foreach (['uz', 'ru'] as $lang) {
                if (! is_string($data["{$field}_$lang"] ?? null) || trim($data["{$field}_$lang"]) === '') {
                    throw new RuntimeException("AI javobida {$field}_$lang yo'q");
                }
            }
        }
        foreach (['uz', 'ru'] as $lang) {
            if (str_word_count(strip_tags($data["body_$lang"]), 0, 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя') < 250) {
                throw new RuntimeException("body_$lang juda qisqa");
            }
        }
    }

    private function uniqueSlug(string $title): string
    {
        // ʻ ' ’ ` belgilari oʻ/gʻ ichida: "oʻzbek" -> "ozbek" (tire bo'lib ketmasin)
        $clean = preg_replace("~[ʻʼ'’‘`]~u", '', $title);
        $base = Str::limit(Str::slug($clean), 80, '') ?: 'maqola';
        $slug = $base;
        for ($i = 2; Article::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $fields = [];
        foreach (['title', 'excerpt', 'body'] as $f) {
            foreach (['uz', 'ru'] as $lang) {
                $fields["{$f}_$lang"] = ['type' => 'string'];
            }
        }

        return ['type' => 'object', 'properties' => $fields, 'required' => array_keys($fields), 'additionalProperties' => false];
    }

    /**
     * @return array{text: ?string, stop_reason: ?string, input_tokens: int, output_tokens: int}
     */
    protected function callApi(string $system, string $userContent, string $model, array $schema): array
    {
        $client = new Client(
            apiKey: config('services.anthropic.key'),
            requestOptions: ['timeout' => 240.0, 'maxRetries' => 1],
        );

        $message = $client->messages->create(
            model: $model,
            maxTokens: 12000,
            system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            messages: [['role' => 'user', 'content' => $userContent]],
            outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
        );

        $text = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text = $block->text;
                break;
            }
        }

        return [
            'text' => $text,
            'stop_reason' => $message->stopReason,
            'input_tokens' => $message->usage->inputTokens,
            'output_tokens' => $message->usage->outputTokens,
        ];
    }
}
